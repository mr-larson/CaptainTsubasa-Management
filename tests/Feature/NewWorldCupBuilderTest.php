<?php

namespace Tests\Feature;

use App\Enums\GamePhase;
use App\Enums\Nationality;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\Player;
use App\Models\User;
use App\Services\WorldCup\NewWorldCupBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Création d'une Coupe du Monde : nations jouables, tirage des adversaires,
 * construction de la partie, et route de lancement.
 */
class NewWorldCupBuilderTest extends TestCase
{
    use RefreshDatabase;

    /** Donne $count joueurs à une nation, dont $goalkeepers gardiens. */
    private function seedNation(string $nation, int $count = 12, int $goalkeepers = 1): void
    {
        foreach (range(1, $count) as $i) {
            Player::factory()->create([
                'nationality' => $nation,
                'position'    => $i <= $goalkeepers ? 'Goalkeeper' : 'Midfielder',
            ]);
        }
    }

    /** Remplit les 8 premières nations de la liste : de quoi disputer un tournoi complet. */
    private function seedTournament(): array
    {
        $nations = array_slice(Nationality::ALL, 0, 8);
        foreach ($nations as $nation) {
            $this->seedNation($nation);
        }

        return $nations;
    }

    private function builder(): NewWorldCupBuilder
    {
        return app(NewWorldCupBuilder::class);
    }

    public function test_a_nation_is_playable_only_with_eleven_players_including_a_goalkeeper(): void
    {
        [$ok, $short, $noKeeper] = array_slice(Nationality::ALL, 0, 3);
        $this->seedNation($ok);
        $this->seedNation($short, 10);
        $this->seedNation($noKeeper, 12, 0);

        $this->assertTrue($this->builder()->isPlayable($ok));
        $this->assertFalse($this->builder()->isPlayable($short), '10 joueurs : pas assez pour un onze');
        $this->assertFalse($this->builder()->isPlayable($noKeeper), 'sans gardien : pas alignable');
    }

    public function test_playable_nations_are_listed_first(): void
    {
        [$ok, $other] = array_slice(Nationality::ALL, 0, 2);
        $this->seedNation($other, 5); // injouable
        $this->seedNation($ok);

        $rows = $this->builder()->playableNations();

        $this->assertSame($ok, $rows[0]['name']);
        $this->assertTrue($rows[0]['playable']);
        $this->assertSame(12, $rows[0]['total']);
        $this->assertFalse(collect($rows)->firstWhere('name', $other)['playable']);
    }

    public function test_build_creates_a_world_cup_with_eight_nations_and_the_chosen_one_controlled(): void
    {
        $nations = $this->seedTournament();
        $user    = User::factory()->create();

        $save = $this->builder()->build($user, ['nation' => $nations[0], 'period' => 'college', 'label' => 'Mondial', 'game_config' => null]);

        $this->assertSame(GamePhase::Group, $save->fresh()->phase); // les poules sont déjà tirées
        $this->assertSame('world_cup', $save->competition_type);
        $this->assertSame('Mondial', $save->label);
        $this->assertSame(8, GameTeam::where('game_save_id', $save->id)->count());

        $controlled = GameTeam::where('game_save_id', $save->id)->where('is_controlled', true)->get();
        $this->assertCount(1, $controlled);
        $this->assertSame($nations[0], $controlled->first()->name);
        $this->assertGreaterThan(0, GameMatch::where('game_save_id', $save->id)->count());
    }

    public function test_the_route_refuses_an_unplayable_nation_and_creates_nothing(): void
    {
        $this->seedTournament();
        $user = User::factory()->create();
        $unplayable = Nationality::ALL[8]; // la 9e nation : jamais alimentée par seedTournament()

        $this->actingAs($user)
            ->post(route('game-saves.start-world-cup'), ['period' => 'college', 'competition_type' => 'world_cup', 'nation' => $unplayable, 'player_ids' => range(1, 11)])
            ->assertSessionHasErrors('nation');

        $this->assertSame(0, GameSave::where('user_id', $user->id)->count());
    }

    public function test_a_playable_nation_has_a_squad_selection_step(): void
    {
        [$nation, $short] = array_slice(Nationality::ALL, 0, 2);
        $this->seedNation($nation, 21);
        $this->seedNation($short, 1);
        $setup = ['period' => 'college', 'competition_type' => 'world_cup'];

        $this->actingAs(User::factory()->create())
            ->withSession(['game_save_setup' => $setup])
            ->get(route('game-saves.setup', ['nation' => $nation]))
            ->assertInertia(fn ($page) => $page->component('GameSaves/NationSelection')
                ->where('squad.name', $nation)->has('squad.players', 21)->has('squad.selectedIds', 18)
                ->has('squad.formation')->has('squad.description'));

        $this->get(route('game-saves.setup', ['nation' => $short]))
            ->assertInertia(fn ($page) => $page->where('squad', null)
                ->where('nations', fn ($nations) => collect($nations)->firstWhere('name', $short)['total'] === 1
                    && collect($nations)->firstWhere('name', $short)['playable'] === false));
    }

    public function test_the_route_rejects_invalid_squads_without_creating_a_save(): void
    {
        [$nation, $other] = array_slice(Nationality::ALL, 0, 2);
        $this->seedNation($nation, 24);
        $this->seedNation($other, 1);
        $ids = Player::where('nationality', $nation)->orderBy('id')->pluck('id')->all();
        $foreignId = Player::where('nationality', $other)->value('id');
        $deletedId = array_pop($ids);
        Player::findOrFail($deletedId)->delete();
        $data = ['period' => 'college', 'competition_type' => 'world_cup', 'nation' => $nation];
        $user = User::factory()->create();
        $this->withoutMiddleware(ThrottleRequests::class)->actingAs($user);

        foreach ([
            [[], 'player_ids'],
            [array_slice($ids, 0, 10), 'player_ids'],
            [array_slice($ids, 0, 21), 'player_ids'],
            [array_slice($ids, 1, 11), 'player_ids'],
            [[...array_slice($ids, 0, 10), $foreignId], 'player_ids'],
            [[...array_slice($ids, 0, 10), $deletedId], 'player_ids'],
            [[...array_slice($ids, 0, 10), $ids[0]], 'player_ids.10'],
        ] as [$selection, $error]) {
            $this->post(route('game-saves.start-world-cup'), $data + ['player_ids' => $selection])
                ->assertSessionHasErrors($error);
        }

        $this->assertSame(0, GameSave::where('user_id', $user->id)->count());
    }

    public function test_the_route_creates_a_lineup_from_exactly_the_selected_players(): void
    {
        $nations = $this->seedTournament();
        $nation = $nations[1];
        $user = User::factory()->create();
        $team = Player::where('nationality', $nation)->orderBy('id')->get();
        $team->slice(1, 5)->each->update(['position' => 'Defender']);
        $team->slice(9, 2)->each->update(['position' => 'Forward']);
        $ids = $team->take(11)->pluck('id')->all();

        $this->actingAs($user)
            ->post(route('game-saves.start-world-cup'), [
                'period' => 'college',
                'competition_type' => 'world_cup',
                'nation' => $nation,
                'player_ids' => $ids,
            ])->assertSessionHasNoErrors()->assertRedirect();

        $save = GameSave::where('user_id', $user->id)->firstOrFail();
        $controlled = GameTeam::findOrFail($save->controlled_game_team_id);
        $this->assertSame($nation, $controlled->name);
        $this->assertSame('5-2-1-2', $controlled->formation);
        $this->assertStringContainsString('5 défenseurs', $controlled->description);
        $contracts = $controlled->contracts()->with('gamePlayer')->get();
        $this->assertEqualsCanonicalizing($ids, $contracts->pluck('gamePlayer.base_player_id')->all());
        $this->assertCount(11, $contracts->where('is_starter', true));
        $slots = $save->gameState()->lineupSlots($controlled->id);
        $this->assertEqualsCanonicalizing($contracts->pluck('game_player_id')->all(), array_values($slots));
        $this->assertSame($contracts->firstWhere('gamePlayer.base_player_id', $ids[0])->game_player_id, $slots[1]);
    }
    public function test_the_route_accepts_twenty_players(): void
    {
        $nation = $this->seedTournament()[0];
        $this->seedNation($nation, 9, 0);
        $ids = Player::where('nationality', $nation)->orderBy('id')->limit(20)->pluck('id')->all();

        $this->actingAs(User::factory()->create())
            ->post(route('game-saves.start-world-cup'), [
                'period' => 'college',
                'competition_type' => 'world_cup',
                'nation' => $nation,
                'player_ids' => $ids,
            ])->assertSessionHasNoErrors()->assertRedirect();

        $team = GameTeam::where('name', $nation)->where('is_controlled', true)->firstOrFail();
        $this->assertEqualsCanonicalizing($ids, $team->contracts()->with('gamePlayer')->get()->pluck('gamePlayer.base_player_id')->all());
        $this->assertSame(11, $team->contracts()->where('is_starter', true)->count());
    }

}

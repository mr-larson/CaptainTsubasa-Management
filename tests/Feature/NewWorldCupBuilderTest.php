<?php

namespace Tests\Feature;

use App\Enums\GamePhase;
use App\Enums\Nationality;
use App\Enums\TeamStyle;
use App\Helpers\FormationHelper;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\Nation;
use App\Models\Player;
use App\Models\User;
use App\Services\WorldCup\NationalSquadPicker;
use App\Services\WorldCup\NationalTeamAssembler;
use App\Services\WorldCup\NewWorldCupBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Création d'une Coupe du Monde : nations jouables, tirage des adversaires,
 * construction de la partie, et route de lancement.
 */
class NewWorldCupBuilderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Les migrations de légendes insèrent de vrais joueurs dans la base de test : on repart d'un vivier
     * vide pour que les comptes de ce test ne dépendent que de ce qu'il crée lui-même.
     */
    protected function setUp(): void
    {
        parent::setUp();
        DB::table('players')->delete();
    }

    /** Donne $count joueurs à une nation, dont $goalkeepers gardiens. */
    private function seedNation(string $nation, int $count = 12, int $goalkeepers = 1): void
    {
        foreach (range(1, $count) as $i) {
            Player::factory()->create([
                'nationality' => $nation,
                'position' => $i <= $goalkeepers ? 'Goalkeeper' : 'Midfielder',
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
        $user = User::factory()->create();

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
        $this->seedTournament();       // 8 nations jouables : de quoi lancer un tournoi
        $this->seedNation($nation, 12); // la nation choisie passe à 24 joueurs
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

    public function test_hot_seat_creates_one_human_team_per_seat_and_draws_the_rest(): void
    {
        $nations = $this->seedTournament();
        $user = User::factory()->create();

        $save = $this->builder()->build($user, [
            'nation' => $nations[0], 'co_nations' => [$nations[3], $nations[5]],
            'period' => 'college', 'label' => 'Hot-seat', 'game_config' => null,
        ]);

        $humans = GameTeam::where('game_save_id', $save->id)->where('is_controlled', true)->orderBy('human_seat')->get();
        $this->assertSame([$nations[0], $nations[3], $nations[5]], $humans->pluck('name')->all());
        $this->assertSame([1, 2, 3], $humans->pluck('human_seat')->map(fn ($s) => (int) $s)->all());
        $this->assertSame($humans[0]->id, (int) $save->controlled_game_team_id);
        $this->assertSame(8, GameTeam::where('game_save_id', $save->id)->count());
        $this->assertSame(5, GameTeam::where('game_save_id', $save->id)->where('is_controlled', false)->count());
    }

    public function test_the_route_accepts_co_nations_and_rejects_the_main_nation_among_them(): void
    {
        $nations = $this->seedTournament();
        $user = User::factory()->create();
        $ids = Player::where('nationality', $nations[0])->orderBy('id')->limit(14)->pluck('id')->all();
        $data = ['period' => 'college', 'competition_type' => 'world_cup', 'nation' => $nations[0], 'player_ids' => $ids];
        $this->withoutMiddleware(ThrottleRequests::class)->actingAs($user);

        $this->post(route('game-saves.start-world-cup'), $data + ['co_nations' => [$nations[0]]])
            ->assertSessionHasErrors('co_nations.0');
        $this->assertSame(0, GameSave::where('user_id', $user->id)->count());

        $this->post(route('game-saves.start-world-cup'), $data + ['co_nations' => [$nations[1]]])
            ->assertSessionHasNoErrors();
        $save = GameSave::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(2, GameTeam::where('game_save_id', $save->id)->where('is_controlled', true)->count());
    }

    public function test_the_nations_catalog_covers_every_nationality_of_the_enum(): void
    {
        $this->assertEqualsCanonicalizing(Nationality::ALL, Nation::pluck('name')->all());
        $this->assertSame(0, Nation::whereNull('code')->orWhere('code', '')->count());
    }

    public function test_assembled_national_teams_are_linked_to_their_catalog_nation(): void
    {
        $nations = $this->seedTournament();
        $save = $this->builder()->build(User::factory()->create(), ['nation' => $nations[0], 'period' => 'college', 'label' => 'x', 'game_config' => null]);

        $teams = GameTeam::where('game_save_id', $save->id)->get();
        $this->assertCount(8, $teams);
        foreach ($teams as $team) {
            $this->assertSame($team->name, $team->baseNation?->name);
        }
    }

    public function test_every_nation_has_a_valid_style_and_philosophy_with_some_variety(): void
    {
        $nations = Nation::all();
        foreach ($nations as $nation) {
            $this->assertContains($nation->tactical_style, TeamStyle::TACTICAL_STYLES, $nation->name);
            $this->assertContains($nation->management_philosophy, TeamStyle::PHILOSOPHIES, $nation->name);
        }
        $this->assertGreaterThanOrEqual(4, $nations->pluck('tactical_style')->unique()->count());
        $this->assertGreaterThanOrEqual(3, $nations->pluck('management_philosophy')->unique()->count());
    }

    public function test_an_ai_squad_fills_the_formation_by_position_not_only_by_rating(): void
    {
        $nation = Nationality::ALL[0];
        // Onze attaquants très bien notés, mais peu de défenseurs/milieux moyens : l'IA doit quand même aligner une défense.
        foreach (range(1, 2) as $i) {
            Player::factory()->create(['nationality' => $nation, 'position' => 'Goalkeeper']);
        }
        foreach (range(1, 10) as $i) {
            Player::factory()->create(['nationality' => $nation, 'position' => 'Forward', 'stats' => ['shot' => 95, 'attack' => 95, 'dribble' => 95, 'speed' => 95]]);
        }
        foreach (range(1, 5) as $i) {
            Player::factory()->create(['nationality' => $nation, 'position' => 'Defender', 'stats' => ['defense' => 50, 'tackle' => 50, 'block' => 50, 'intercept' => 50]]);
        }
        foreach (range(1, 5) as $i) {
            Player::factory()->create(['nationality' => $nation, 'position' => 'Midfielder']);
        }

        [$squad, $lineup] = app(NationalSquadPicker::class)->pick(Player::where('nationality', $nation)->get(), null);

        $this->assertCount(18, $squad);
        $this->assertCount(11, $lineup['slots']);
        $this->assertArrayHasKey($lineup['formation'], FormationHelper::SLOT_ZONES);
        // Le schéma retenu dépend maintenant du niveau réel des joueurs : la défense alignée doit
        // simplement remplir les postes de défenseur de ce schéma (et non onze attaquants).
        $defenseSlots = count(array_filter(FormationHelper::SLOT_ZONES[$lineup['formation']], fn ($zone) => $zone === 1));
        $startingDefenders = $squad->whereIn('id', array_values($lineup['slots']))->filter(fn ($p) => $p->position->value === 'Defender')->count();
        $this->assertGreaterThanOrEqual(3, $startingDefenders);
        $this->assertSame($defenseSlots, $startingDefenders);
    }

    public function test_an_inactive_nation_is_neither_listed_nor_accepted(): void
    {
        $nations = $this->seedTournament();
        Nation::where('name', $nations[1])->update(['is_active' => false]);
        $user = User::factory()->create();

        $this->assertNotContains($nations[1], array_column($this->builder()->playableNations(), 'name'));

        $this->withoutMiddleware(ThrottleRequests::class)->actingAs($user)
            ->post(route('game-saves.start-world-cup'), [
                'period' => 'college', 'competition_type' => 'world_cup', 'nation' => $nations[1],
                'player_ids' => Player::where('nationality', $nations[1])->orderBy('id')->limit(14)->pluck('id')->all(),
            ])->assertSessionHasErrors('nation');
        $this->assertSame(0, GameSave::where('user_id', $user->id)->count());
    }

    public function test_a_favourite_formation_is_used_only_when_the_squad_can_fill_it(): void
    {
        $nation = Nationality::ALL[0];
        // 2 gardiens, 3 défenseurs seulement : un schéma à 5 défenseurs n'est pas jouable.
        foreach (range(1, 2) as $i) {
            Player::factory()->create(['nationality' => $nation, 'position' => 'Goalkeeper']);
        }
        foreach (range(1, 3) as $i) {
            Player::factory()->create(['nationality' => $nation, 'position' => 'Defender']);
        }
        foreach (range(1, 6) as $i) {
            Player::factory()->create(['nationality' => $nation, 'position' => 'Midfielder']);
        }
        foreach (range(1, 5) as $i) {
            Player::factory()->create(['nationality' => $nation, 'position' => 'Forward']);
        }
        $picker = app(NationalSquadPicker::class);
        $pool = Player::where('nationality', $nation)->get();

        $this->assertNotSame('5-3-1-1', $picker->lineupFor($pool, '5-3-1-1')['formation']);
        $this->assertSame('4-3-2-1', $picker->lineupFor($pool->merge(
            collect(range(1, 2))->map(fn () => Player::factory()->create(['nationality' => $nation, 'position' => 'Defender']))
        ), '4-3-2-1')['formation']);
    }

    public function test_every_nation_has_a_valid_favourite_formation(): void
    {
        foreach (Nation::all() as $nation) {
            $this->assertArrayHasKey($nation->default_formation, FormationHelper::SLOT_ZONES, $nation->name);
        }
    }

    public function test_every_national_team_starts_with_the_same_budget(): void
    {
        $nations = $this->seedTournament();
        $save = $this->builder()->build(User::factory()->create(), ['nation' => $nations[0], 'period' => 'college', 'label' => 'x', 'game_config' => null]);

        $this->assertSame([NationalTeamAssembler::START_BUDGET], GameTeam::where('game_save_id', $save->id)->pluck('budget')->unique()->map(fn ($b) => (int) $b)->all());
    }

    public function test_the_number_of_humans_is_capped_by_the_tournament_size(): void
    {
        $this->assertSame(2, NewWorldCupBuilder::maxHumans(8));
        $this->assertSame(4, NewWorldCupBuilder::maxHumans(16));

        $nations = $this->seedTournament();
        $user = User::factory()->create();
        $this->withoutMiddleware(ThrottleRequests::class)->actingAs($user)
            ->post(route('game-saves.start-world-cup'), [
                'period' => 'college', 'competition_type' => 'world_cup', 'nation' => $nations[0], 'co_nations' => [$nations[1], $nations[2]],
                'player_ids' => Player::where('nationality', $nations[0])->orderBy('id')->limit(14)->pluck('id')->all(),
            ])->assertSessionHasErrors('co_nations');
        $this->assertSame(0, GameSave::where('user_id', $user->id)->count());
    }

    public function test_a_co_manager_squad_and_imposed_opponents_are_honoured(): void
    {
        $nations = $this->seedTournament();
        $co = Player::where('nationality', $nations[1])->orderBy('id')->limit(12)->pluck('id')->all();

        $save = $this->builder()->build(User::factory()->create(), [
            'nation' => $nations[0], 'co_nations' => [$nations[1]], 'co_player_ids' => [$nations[1] => $co],
            'opponents' => [$nations[7]], 'period' => 'college', 'label' => 'x', 'game_config' => null,
        ]);

        $coTeam = GameTeam::where('game_save_id', $save->id)->where('name', $nations[1])->firstOrFail();
        $this->assertSame(12, GameContract::where('game_team_id', $coTeam->id)->count());
        $this->assertSame(2, (int) $coTeam->human_seat);
        $this->assertTrue(GameTeam::where('game_save_id', $save->id)->where('name', $nations[7])->exists());
    }

    public function test_the_setup_step_returns_a_squad_preview_for_each_co_manager(): void
    {
        $nations = $this->seedTournament();
        $setup = ['period' => 'college', 'competition_type' => 'world_cup'];

        $this->actingAs(User::factory()->create())->withSession(['game_save_setup' => $setup])
            ->get(route('game-saves.setup', ['nation' => $nations[0], 'co_nations' => [$nations[1]], 'opponents' => [$nations[2]], 'nations_count' => 8]))
            ->assertInertia(fn ($page) => $page->component('GameSaves/NationSelection')
                ->where('coNations', [$nations[1]])->where('opponents', [$nations[2]])
                ->has('coSquads', 1)->where('coSquads.0.name', $nations[1])->where('maxHumans', 2));
    }
}

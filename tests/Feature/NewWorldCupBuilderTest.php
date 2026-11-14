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
            ->post(route('game-saves.start-world-cup'), ['period' => 'college', 'competition_type' => 'world_cup', 'nation' => $unplayable])
            ->assertSessionHasErrors('nation');

        $this->assertSame(0, GameSave::where('user_id', $user->id)->count());
    }

    public function test_the_route_creates_the_tournament_for_a_playable_nation(): void
    {
        $nations = $this->seedTournament();
        $user    = User::factory()->create();

        $this->actingAs($user)
            ->post(route('game-saves.start-world-cup'), ['period' => 'college', 'competition_type' => 'world_cup', 'nation' => $nations[1]])
            ->assertRedirect();

        $save = GameSave::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('world_cup', $save->competition_type);
        $this->assertSame($nations[1], GameTeam::where('game_save_id', $save->id)->where('is_controlled', true)->value('name'));
    }
}

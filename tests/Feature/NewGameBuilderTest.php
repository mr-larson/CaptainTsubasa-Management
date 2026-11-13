<?php

namespace Tests\Feature;

use App\Enums\GamePhase;
use App\Models\Contract;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameTeam;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Services\NewGameBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Construction d'une partie Ligue appelée directement (sans passer par la route) :
 * ce que le service garantit quel que soit le contrôleur qui l'utilise.
 */
class NewGameBuilderTest extends TestCase
{
    use RefreshDatabase;

    /** Trois équipes de 13 joueurs sous contrat dans les tables de référence. */
    private function seedReferenceData(): array
    {
        $teams = Team::factory()->count(3)->create();
        foreach ($teams as $team) {
            foreach (Player::factory()->count(13)->create() as $player) {
                Contract::create(['team_id' => $team->id, 'player_id' => $player->id, 'salary' => 10, 'is_starter' => false, 'is_captain' => false]);
            }
        }

        return $teams->all();
    }

    private function data(array $extra = []): array
    {
        return array_merge(['period' => 'college', 'game_mode' => 'prebuilt', 'game_config' => null], $extra);
    }

    public function test_prebuilt_game_copies_teams_players_and_numbers_a_full_squad(): void
    {
        [$a, $b] = $this->seedReferenceData();
        $user = User::factory()->create();

        $save = app(NewGameBuilder::class)->build($user, $this->data(['team_id' => $a->id]), null, 123);

        $this->assertSame(GamePhase::Season, $save->phase);
        $this->assertSame(3, GameTeam::where('game_save_id', $save->id)->count());
        $this->assertSame(39, GamePlayer::where('game_save_id', $save->id)->whereNotNull('base_player_id')->count());
        $this->assertSame(39, GameContract::where('game_save_id', $save->id)->count());
        $this->assertSame($a->id, $save->team_id);

        // Dans chaque équipe : 11 titulaires, 2 remplaçants.
        foreach (GameContract::where('game_save_id', $save->id)->get()->groupBy('game_team_id') as $contracts) {
            $this->assertSame(11, $contracts->where('is_starter', true)->count());
        }
    }

    public function test_draft_game_creates_players_but_no_contracts_and_prepares_the_draft(): void
    {
        [$a] = $this->seedReferenceData();

        $save = app(NewGameBuilder::class)->build(
            User::factory()->create(),
            $this->data(['game_mode' => 'draft', 'team_id' => $a->id]),
            null,
            7,
        );

        $this->assertSame(GamePhase::Draft, $save->phase);
        $this->assertSame(0, GameContract::where('game_save_id', $save->id)->count());
        $this->assertNotEmpty($save->fresh()->state['draft'] ?? []);
    }

    public function test_hot_seat_teams_get_seats_in_the_order_chosen_and_seat_one_is_active(): void
    {
        [$a, $b, $c] = $this->seedReferenceData();

        $save = app(NewGameBuilder::class)->build(
            User::factory()->create(),
            $this->data(['team_ids' => [$c->id, $a->id]]),
            null,
            1,
        );

        $seats = GameTeam::where('game_save_id', $save->id)->where('is_controlled', true)->orderBy('human_seat')->pluck('base_team_id')->all();
        $this->assertSame([$c->id, $a->id], $seats);
        $this->assertSame(
            GameTeam::where('game_save_id', $save->id)->where('human_seat', 1)->value('id'),
            $save->controlled_game_team_id,
        );
    }

    public function test_same_seed_gives_the_same_initial_stats(): void
    {
        [$a] = $this->seedReferenceData();
        $config = ['initial_stamina_random' => true, 'initial_speed_random' => true];
        $builder = app(NewGameBuilder::class);
        $user = User::factory()->create();

        $first  = $builder->build($user, $this->data(['team_id' => $a->id, 'game_config' => $config]), null, 555);
        $second = $builder->build($user, $this->data(['team_id' => $a->id, 'game_config' => $config]), null, 555);

        $stats = fn ($save) => GamePlayer::where('game_save_id', $save->id)->whereNotNull('base_player_id')
            ->orderBy('base_player_id')->get(['speed', 'stamina'])->map->only(['speed', 'stamina'])->all();

        $this->assertSame($stats($first), $stats($second));

        // Une autre graine donne d'autres stats : le tirage aléatoire est bien actif.
        $other = $builder->build($user, $this->data(['team_id' => $a->id, 'game_config' => $config]), null, 556);
        $this->assertNotSame($stats($first), $stats($other));
    }
}

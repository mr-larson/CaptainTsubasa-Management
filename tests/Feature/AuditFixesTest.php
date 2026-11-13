<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSave;
use App\Models\User;
use App\Services\PostMatchProgressionService;
use App\Services\SeasonService;
use App\Services\StaminaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Correctifs issus de l'audit du 2026-11-04 (audit-projet.md) : réglages de
 * partie bornés, quota d'entraînement côté serveur, durée de saison unique,
 * détail des matchs à la demande, progression limitée à la save, stamina en lot.
 */
class AuditFixesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_creation_rejects_out_of_range_game_config(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('game-saves.store'), [
            'period'      => 'college',
            'game_config' => ['match_max_turns' => 100000000, 'training_cost' => -5000],
        ])->assertSessionHasErrors(['game_config.match_max_turns', 'game_config.training_cost']);

        $this->assertNull(session('game_save_setup'));
    }

    public function test_creation_keeps_only_known_config_keys(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('game-saves.store'), [
            'period'      => 'college',
            'game_config' => [
                'match_max_turns' => 30,
                'not_a_setting'   => 'x',
                'visible_origins' => ['captain_tsubasa' => true, 'unknown_origin' => true],
            ],
        ])->assertSessionHasNoErrors();

        $config = session('game_save_setup')['game_config'];
        $this->assertSame(30, (int) $config['match_max_turns']);
        $this->assertArrayNotHasKey('not_a_setting', $config);
        $this->assertSame(['captain_tsubasa'], array_keys($config['visible_origins']));
    }

    public function test_creation_rejects_gain_min_above_gain_max(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('game-saves.store'), [
            'period'      => 'college',
            'game_config' => ['training_gain_min' => 8, 'training_gain_max' => 3],
        ])->assertSessionHasErrors('game_config');
    }

    public function test_training_week_comes_from_the_save_not_the_client(): void
    {
        $save = $this->makeSave(['week' => 3]);
        $team = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $save->update(['controlled_game_team_id' => $team->id]);
        $player = $this->makePlayer($save, ['shot' => 50]);
        $this->makeContract($save, $team, $player);

        // week=1000 remettait le quota à zéro : refusé, rien n'est appliqué.
        $this->actingAs(User::find($save->user_id))
            ->post(route('game-saves.training.store', $save), [
                'season'    => 1,
                'week'      => 1000,
                'trainings' => [['player_id' => $player->id, 'stat' => 'shot']],
            ])
            ->assertSessionHasErrors('trainings');

        $this->assertSame(50, (int) $player->fresh()->shot);
        $this->assertArrayNotHasKey('training', $save->fresh()->state ?? []);
    }

    public function test_season_length_counts_a_bye_round_for_odd_team_counts(): void
    {
        $this->assertSame(10, SeasonService::lengthForTeamCount(5));
        $this->assertSame(10, SeasonService::lengthForTeamCount(6));
        $this->assertSame(14, SeasonService::lengthForTeamCount(8));
        $this->assertSame(28, SeasonService::lengthForTeamCount(1));
    }

    public function test_match_stats_are_loaded_on_demand_and_scoped(): void
    {
        $save  = $this->makeSave();
        $home  = $this->makeTeam($save);
        $away  = $this->makeTeam($save);
        $match = $this->makeMatch($save, $home, $away, [
            'status' => 'played', 'home_score' => 1, 'away_score' => 0,
            'match_stats' => ['events' => [['turn' => 1]]],
        ]);
        $owner = User::find($save->user_id);

        $this->actingAs($owner)
            ->getJson(route('game-saves.matches.stats', [$save, $match]))
            ->assertOk()
            ->assertJsonPath('match_stats.events.0.turn', 1);

        // Un autre utilisateur ne lit pas le détail.
        $this->actingAs(User::factory()->create())
            ->getJson(route('game-saves.matches.stats', [$save, $match]))
            ->assertForbidden();

        // Un match d'une autre save, même avec ma save dans l'URL : refusé.
        $other      = $this->makeSave(['user_id' => $owner->id]);
        $otherMatch = $this->makeMatch($other, $this->makeTeam($other), $this->makeTeam($other));
        $this->actingAs($owner)
            ->getJson(route('game-saves.matches.stats', [$save, $otherMatch]))
            ->assertNotFound();
    }

    public function test_progression_ignores_players_from_another_save(): void
    {
        $save  = $this->makeSave();
        $home  = $this->makeTeam($save);
        $away  = $this->makeTeam($save);
        $mine  = $this->makePlayer($save, ['shot' => 50]);
        $this->makeContract($save, $home, $mine);

        $victimSave = $this->makeSave();
        $foreign    = $this->makePlayer($victimSave, ['shot' => 50]);

        $bigGame = ['offense' => ['goals' => 5, 'shots' => 10], 'defense' => []];
        $match   = $this->makeMatch($save, $home, $away, [
            'status'      => 'played',
            'match_stats' => ['players' => [
                (string) $mine->id    => $bigGame,
                (string) $foreign->id => $bigGame,
            ]],
        ]);

        app(PostMatchProgressionService::class)->applyForMatch($save, $match);

        $this->assertGreaterThan(50, (int) $mine->fresh()->shot);
        $this->assertSame(50, (int) $foreign->fresh()->shot);
    }

    public function test_weekly_stamina_is_bounded_and_counts_each_player_once(): void
    {
        $save = $this->makeSave();
        $home = $this->makeTeam($save);
        $away = $this->makeTeam($save);

        $tired   = $this->makePlayer($save, ['stamina' => 3]);
        $fresh   = $this->makePlayer($save, ['stamina' => 95]);
        $resting = $this->makePlayer($save, ['stamina' => 40]);
        $this->makeContract($save, $home, $tired);
        $this->makeContract($save, $home, $fresh);
        $this->makeContract($save, $away, $resting);

        $this->makeMatch($save, $home, $away, [
            'status'      => 'played',
            'match_stats' => ['players' => [(string) $tired->id => [], (string) $fresh->id => []]],
        ]);

        StaminaService::applyAfterWeek($save, 1);

        // Coût 5 / récupération 10 (config par défaut) ; UNSIGNED borné à 0.
        $this->assertSame(0, (int) $tired->fresh()->stamina);
        $this->assertSame(90, (int) $fresh->fresh()->stamina);
        $this->assertSame(50, (int) $resting->fresh()->stamina);
    }
}

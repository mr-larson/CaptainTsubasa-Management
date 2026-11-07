<?php

namespace Tests\Feature;

use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Models\GameSaves\GamePromise;
use App\Services\PlayerMoraleSummary;
use App\Services\PromiseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Promesses du coach (PromiseService) : création, évaluation (temps de jeu,
 * titularisation, prolongation) et effets sur l'affinité/le moral. Une
 * promesse tenue récompense, une promesse rompue pénalise fortement.
 */
class PromiseServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private PromiseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PromiseService::class);
    }

    public function test_create_sets_due_week_from_type_window(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $this->makeContract($save, $team, $player);

        $promise = $this->service->create($save, $player, $team->id, 'playing_time');

        $this->assertInstanceOf(GamePromise::class, $promise);
        $this->assertSame(1, $promise->start_week);
        $this->assertSame(5, $promise->due_week); // fenêtre de 5 semaines
        $this->assertSame(10, $promise->target_turns); // un tiers des 30 tours par défaut
        // status a un défaut SQL ('pending') appliqué au rechargement.
        $this->assertSame('pending', $promise->fresh()->status);
    }

    public function test_playing_time_target_follows_match_length(): void
    {
        $save = $this->makeSave(['week' => 1, 'state' => ['config' => ['match_max_turns' => 45]]]);
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $this->makeContract($save, $team, $player);

        $promise = $this->service->create($save, $player, $team->id, 'playing_time');

        $this->assertSame(15, $promise->target_turns);
    }

    public function test_create_rejects_unknown_type_and_duplicates(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $this->makeContract($save, $team, $player);

        $this->assertIsString($this->service->create($save, $player, $team->id, 'wat'));

        $this->service->create($save, $player, $team->id, 'playing_time');
        $this->assertIsString(
            $this->service->create($save, $player, $team->id, 'playing_time'),
            'Une seconde promesse en cours doit être refusée.'
        );
    }

    public function test_playing_time_promise_is_kept_when_player_plays_enough(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $opp  = $this->makeTeam($save);
        $player = $this->makePlayer($save, ['coach_affinity' => 0, 'morale' => 60]);
        $this->makeContract($save, $team, $player);

        $this->service->create($save, $player, $team->id, 'playing_time');

        // Match joué où le joueur a fait 45 tours (≥ 15 promis).
        $this->makeMatch($save, $team, $opp, [
            'week' => 1, 'status' => 'played', 'home_score' => 1, 'away_score' => 0,
            'match_stats' => ['playtime' => [(string) $player->id => 45]],
        ]);

        $this->service->evaluateForWeek($save, 1);

        $promise = GamePromise::where('game_player_id', $player->id)->first();
        $this->assertSame('kept', $promise->status);
        $this->assertSame(5, (int) $player->fresh()->coach_affinity); // +5
        $this->assertSame(65, (int) $player->fresh()->morale);        // +5
    }

    public function test_playing_time_promise_is_broken_when_player_barely_plays(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $opp  = $this->makeTeam($save);
        $player = $this->makePlayer($save, ['coach_affinity' => 0, 'morale' => 60]);
        $this->makeContract($save, $team, $player);

        $this->service->create($save, $player, $team->id, 'playing_time');

        $this->makeMatch($save, $team, $opp, [
            'week' => 1, 'status' => 'played', 'home_score' => 1, 'away_score' => 0,
            'match_stats' => ['playtime' => [(string) $player->id => 5]], // < 15
        ]);

        $this->service->evaluateForWeek($save, 1);

        $promise = GamePromise::where('game_player_id', $player->id)->first();
        $this->assertSame('broken', $promise->status);
        $this->assertSame(-10, (int) $player->fresh()->coach_affinity);
        $this->assertSame(50, (int) $player->fresh()->morale);

        // Une promesse rompue est journalisée.
        $this->assertTrue(
            GamePlayerMoraleLog::where('game_player_id', $player->id)->where('source', 'promise')->exists()
        );
    }

    public function test_pending_starter_promise_shows_and_saves_progress_before_the_deadline(): void
    {
        $save = $this->makeSave(['week' => 2]);
        $team = $this->makeTeam($save);
        $opp = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $this->makeContract($save, $team, $player);

        $promise = $this->service->create($save, $player, $team->id, 'starter');
        foreach ([
            [1, 'played', [$player->id]],
            [2, 'played', [$player->id]],
            [3, 'played', []],
            [4, 'scheduled', [$player->id]],
        ] as [$week, $status, $starters]) {
            $this->makeMatch($save, $opp, $team, [
                'week' => $week,
                'status' => $status,
                'match_stats' => [
                    'starters' => ['home' => [], 'away' => $starters],
                    'playtime' => [$player->id => 15],
                ],
            ]);
        }

        // Le match compte dans l'interface même avant la clôture de la semaine.
        $summary = app(PlayerMoraleSummary::class)->promises($save, [$player->id]);
        $this->assertSame(1, $summary[$player->id][0]['played_matches']);

        $this->service->evaluateForWeek($save, 2);
        $this->service->evaluateForWeek($save, 2);
        $this->assertSame('pending', $promise->fresh()->status);
        $this->assertSame(1, $promise->fresh()->played_matches);
        $this->assertFalse(GamePlayerMoraleLog::where('game_player_id', $player->id)->exists());

        // L'entrée en jeu en semaine 3 et le match programmé ne comptent pas.
        $this->service->evaluateForWeek($save, 4);
        $this->assertSame(1, $promise->fresh()->played_matches);
    }

    public function test_starter_promise_is_kept_when_target_met(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $opp  = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $this->makeContract($save, $team, $player);

        $this->service->create($save, $player, $team->id, 'starter'); // cible 4 titularisations, fenêtre 5

        // 4 matchs où le joueur figure dans le 11 de départ.
        foreach (range(1, 4) as $w) {
            $this->makeMatch($save, $team, $opp, [
                'week' => $w, 'status' => 'played', 'home_score' => 1, 'away_score' => 0,
                'match_stats' => ['starters' => ['home' => [(string) $player->id], 'away' => []]],
            ]);
        }

        // Évaluation à l'échéance (semaine 5).
        $this->service->evaluateForWeek($save, 5);

        $this->assertSame('kept', GamePromise::where('game_player_id', $player->id)->first()->status);
    }

    public function test_starter_promise_is_broken_when_target_missed(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $opp  = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $this->makeContract($save, $team, $player);

        $this->service->create($save, $player, $team->id, 'starter');

        // Un seul match, et le joueur n'est pas titulaire.
        $this->makeMatch($save, $team, $opp, [
            'week' => 1, 'status' => 'played', 'home_score' => 1, 'away_score' => 0,
            'match_stats' => ['starters' => ['home' => [], 'away' => []]],
        ]);

        $this->service->evaluateForWeek($save, 5);

        $this->assertSame('broken', GamePromise::where('game_player_id', $player->id)->first()->status);
    }

    public function test_renewal_promise_is_broken_without_new_contract(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $player = $this->makePlayer($save);
        $this->makeContract($save, $team, $player); // contrat antérieur à la promesse

        $this->service->create($save, $player, $team->id, 'renewal'); // due_week = 4

        // Aucun nouveau contrat signé après la promesse → rompue à l'échéance.
        $this->service->evaluateForWeek($save, 4);

        $this->assertSame('broken', GamePromise::where('game_player_id', $player->id)->first()->status);
    }

    public function test_rest_promise_requires_a_week_without_playing_or_training(): void
    {
        $save = $this->makeSave(['week' => 3]);
        $team = $this->makeTeam($save);
        $opponent = $this->makeTeam($save);
        $rested = $this->makePlayer($save);
        $substitute = $this->makePlayer($save);
        $trained = $this->makePlayer($save);

        foreach ([$rested, $substitute, $trained] as $player) {
            $this->makeContract($save, $team, $player, ['is_starter' => false]);
        }
        $restPromise = $this->service->create($save, $rested, $team->id, 'rest');
        $subPromise = $this->service->create($save, $substitute, $team->id, 'rest');
        $trainingPromise = $this->service->create($save, $trained, $team->id, 'rest');

        $this->assertSame(3, $restPromise->due_week);
        $this->makeMatch($save, $team, $opponent, [
            'week' => 3,
            'status' => 'played',
            'match_stats' => ['starters' => ['home' => [], 'away' => []], 'playtime' => [$substitute->id => 1]],
        ]);
        $save->update(['state' => ['training' => [
            'season' => 1, 'week' => 3, 'entries' => [['player_id' => $trained->id]],
        ]]]);

        $this->service->evaluateForWeek($save, 2);
        $this->assertSame('pending', $restPromise->fresh()->status);
        $this->service->evaluateForWeek($save, 3);

        $this->assertSame('kept', $restPromise->fresh()->status);
        $this->assertSame('broken', $subPromise->fresh()->status);
        $this->assertSame(1, $subPromise->fresh()->played_turns);
        $this->assertSame('broken', $trainingPromise->fresh()->status);
    }
}

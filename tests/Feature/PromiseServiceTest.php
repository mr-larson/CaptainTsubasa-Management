<?php

namespace Tests\Feature;

use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Models\GameSaves\GamePromise;
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
        $this->assertSame(15, $promise->target_turns);
        // status a un défaut SQL ('pending') appliqué au rechargement.
        $this->assertSame('pending', $promise->fresh()->status);
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
        $this->assertSame(15, (int) $player->fresh()->coach_affinity); // +15
        $this->assertSame(65, (int) $player->fresh()->morale);         // +5
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
        $this->assertSame(-25, (int) $player->fresh()->coach_affinity);
        $this->assertSame(50, (int) $player->fresh()->morale);

        // Une promesse rompue est journalisée.
        $this->assertTrue(
            GamePlayerMoraleLog::where('game_player_id', $player->id)->where('source', 'promise')->exists()
        );
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
}

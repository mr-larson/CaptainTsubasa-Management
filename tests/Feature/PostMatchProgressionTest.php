<?php

namespace Tests\Feature;

use App\Services\PostMatchProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Progression post-match (PostMatchProgressionService) : conversion des
 * performances en gains de stats, plafond par saison, et persistance dans
 * state['season_progression']. Service très « stateful », au cœur de
 * l'évolution des joueurs.
 */
class PostMatchProgressionTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private PostMatchProgressionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PostMatchProgressionService::class);
    }

    /** Fabrique un bloc de stats offensives marquant N buts pour un joueur. */
    private function offenseStats(int $goals): array
    {
        return [
            'offense' => ['goals' => $goals],
            'defense' => [],
            'duelsWon' => 0,
        ];
    }

    public function test_goals_translate_into_a_shot_stat_gain(): void
    {
        $save   = $this->makeSave();
        [$team] = $this->makeTeamWithSquad($save);
        $scorer = $this->makePlayer($save, ['position' => 'ATT', 'shot' => 50]);
        $this->makeContract($save, $team, $scorer);

        $match = $this->makeMatch($save, $team, $team, [
            'status'      => 'played',
            'home_score'  => 2,
            'away_score'  => 0,
            'match_stats' => ['players' => [(string) $scorer->id => $this->offenseStats(2)]],
        ]);

        $report = $this->service->applyForMatch($save, $match);

        // 2 buts → +2 en tir (plafond de 2 par match).
        $this->assertSame(52, (int) $scorer->fresh()->shot);
        $this->assertNotEmpty($report);
        $this->assertSame($scorer->id, $report[0]['player_id']);

        // Suivi cumulé dans le state.
        $this->assertSame(2, (int) $save->fresh()->state['season_progression'][1][$scorer->id]);
    }

    public function test_progression_is_capped_per_season(): void
    {
        $save   = $this->makeSave();
        [$team] = $this->makeTeamWithSquad($save);
        $player = $this->makePlayer($save, ['position' => 'ATT', 'shot' => 50]);
        $this->makeContract($save, $team, $player);

        // Le joueur a déjà engrangé 39 points cette saison (plafond = 40).
        $save->state = ['season_progression' => [1 => [$player->id => 39]]];
        $save->save();

        $match = $this->makeMatch($save, $team, $team, [
            'status'      => 'played',
            'home_score'  => 2,
            'away_score'  => 0,
            'match_stats' => ['players' => [(string) $player->id => $this->offenseStats(2)]],
        ]);

        $this->service->applyForMatch($save, $match);

        // Seul 1 point peut encore être gagné avant d'atteindre 40.
        $this->assertSame(51, (int) $player->fresh()->shot);
        $this->assertSame(40, (int) $save->fresh()->state['season_progression'][1][$player->id]);
    }

    public function test_player_at_season_cap_gains_nothing(): void
    {
        $save   = $this->makeSave();
        [$team] = $this->makeTeamWithSquad($save);
        $player = $this->makePlayer($save, ['position' => 'ATT', 'shot' => 60]);
        $this->makeContract($save, $team, $player);

        $save->state = ['season_progression' => [1 => [$player->id => 40]]];
        $save->save();

        $match = $this->makeMatch($save, $team, $team, [
            'status'      => 'played',
            'match_stats' => ['players' => [(string) $player->id => $this->offenseStats(2)]],
        ]);

        $report = $this->service->applyForMatch($save, $match);

        $this->assertSame(60, (int) $player->fresh()->shot, 'Aucun gain au-delà du plafond.');
        $this->assertEmpty($report);
    }

    public function test_empty_match_stats_yield_no_progression(): void
    {
        $save   = $this->makeSave();
        [$team] = $this->makeTeamWithSquad($save);
        $match  = $this->makeMatch($save, $team, $team, ['status' => 'played', 'match_stats' => []]);

        $report = $this->service->applyForMatch($save, $match);

        $this->assertSame([], $report);
    }
}

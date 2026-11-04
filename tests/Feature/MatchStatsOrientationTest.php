<?php

namespace Tests\Feature;

use App\Services\MatchStatsOrientation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Réparation des match_stats enregistrés en repère « équipe humaine = home »
 * pour des matchs joués à l'extérieur.
 */
class MatchStatsOrientationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    /** Stats telles que le moteur les enregistrait : $internalPlayers notés « home ». */
    private function engineStats($internalPlayers, $externalPlayers, int $internalGoals, int $externalGoals): array
    {
        return [
            'teams'    => ['home' => ['goals' => $internalGoals, 'shots' => 9], 'away' => ['goals' => $externalGoals, 'shots' => 10]],
            'starters' => ['home' => $internalPlayers->pluck('id')->all(), 'away' => $externalPlayers->pluck('id')->all()],
            'events'   => [
                ['team' => 'internal', 'actionType' => 'shot-goal', 'meta' => ['meta' => ['attacker' => ['id' => 1, 'team' => 'internal']]]],
                ['team' => 'external', 'actionType' => 'pass-success'],
            ],
        ];
    }

    public function test_an_away_human_match_is_flipped_once(): void
    {
        $save = $this->makeSave();
        [$ai, $aiPlayers]       = $this->makeTeamWithSquad($save);
        [$human, $humanPlayers] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);

        // L'humain joue à l'extérieur et gagne 0-2 ; le moteur l'a noté « home ».
        $match = $this->makeMatch($save, $ai, $human, [
            'status' => 'played', 'home_score' => 0, 'away_score' => 2,
            'match_stats' => $this->engineStats($humanPlayers, $aiPlayers, 2, 0),
        ]);

        $service = app(MatchStatsOrientation::class);
        $this->assertTrue($service->repair($match));

        $stats = $match->fresh()->match_stats;
        $this->assertSame(0, $stats['teams']['home']['goals']);
        $this->assertSame(2, $stats['teams']['away']['goals']);
        $this->assertSame($aiPlayers->pluck('id')->all(), $stats['starters']['home']);
        $this->assertSame('external', $stats['events'][0]['team']);
        $this->assertSame('external', $stats['events'][0]['meta']['meta']['attacker']['team']);
        $this->assertSame('internal', $stats['events'][1]['team']);

        // Idempotent
        $this->assertFalse($service->repair($match->fresh()));
    }

    public function test_a_draw_is_detected_from_the_starters(): void
    {
        $save = $this->makeSave();
        [$ai, $aiPlayers]       = $this->makeTeamWithSquad($save);
        [$human, $humanPlayers] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);

        $match = $this->makeMatch($save, $ai, $human, [
            'status' => 'played', 'home_score' => 1, 'away_score' => 1,
            'match_stats' => $this->engineStats($humanPlayers, $aiPlayers, 1, 1),
        ]);

        $this->assertTrue(app(MatchStatsOrientation::class)->isInverted($match));
    }

    public function test_home_and_simulated_matches_are_left_untouched(): void
    {
        $save = $this->makeSave();
        [$home, $homePlayers] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        [$away, $awayPlayers] = $this->makeTeamWithSquad($save);

        $stats = $this->engineStats($homePlayers, $awayPlayers, 3, 1);
        $match = $this->makeMatch($save, $home, $away, [
            'status' => 'played', 'home_score' => 3, 'away_score' => 1, 'match_stats' => $stats,
        ]);

        $this->assertFalse(app(MatchStatsOrientation::class)->repair($match));
        $this->assertSame(3, $match->fresh()->match_stats['teams']['home']['goals']);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Services\MatchResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Enregistrement du résultat d'un match joué à la main, testé directement sur le
 * service : score, classement, tirs au but, et gardes d'idempotence.
 */
class MatchResultServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function world(array $matchAttrs = []): array
    {
        $save = $this->makeSave();
        [$home] = $this->makeTeamWithSquad($save, ['is_controlled' => true]);
        [$away] = $this->makeTeamWithSquad($save);
        $save->update(['controlled_game_team_id' => $home->id]);

        return [$save->fresh(), $home, $away, $this->makeMatch($save, $home, $away, $matchAttrs)];
    }

    private function service(): MatchResultService
    {
        return app(MatchResultService::class);
    }

    public function test_record_saves_the_score_and_updates_the_standings(): void
    {
        [$save, $home, $away, $match] = $this->world();

        $this->service()->record($save, $match, 3, 1, ['players' => []], []);

        $match->refresh();
        $this->assertSame(MatchStatus::Played, $match->status);
        $this->assertSame([3, 1], [(int) $match->home_score, (int) $match->away_score]);

        $home->refresh();
        $away->refresh();
        $this->assertSame([1, 0, 0, 3, 1], [(int) $home->wins, (int) $home->draws, (int) $home->losses, (int) $home->goals_for, (int) $home->goals_against]);
        $this->assertSame([0, 0, 1, 1, 3], [(int) $away->wins, (int) $away->draws, (int) $away->losses, (int) $away->goals_for, (int) $away->goals_against]);
    }

    public function test_a_draw_gives_one_draw_to_each_team(): void
    {
        [$save, $home, $away, $match] = $this->world();

        $this->service()->record($save, $match, 2, 2, null, []);

        $this->assertSame(1, (int) $home->fresh()->draws);
        $this->assertSame(1, (int) $away->fresh()->draws);
    }

    public function test_a_shootout_outside_a_knockout_tie_is_discarded(): void
    {
        [$save, $home, $away, $match] = $this->world(); // match de ligue : pas de round « semi » / « final »

        $this->service()->record($save, $match, 1, 1, ['shootout' => ['winner_team_id' => $home->id]], []);

        $this->assertArrayNotHasKey('shootout', $match->fresh()->match_stats);
    }

    public function test_a_valid_knockout_shootout_is_kept(): void
    {
        [$save, $home, $away, $match] = $this->world(['round' => 'semi']);

        $this->service()->record($save, $match, 1, 1, ['shootout' => ['winner_team_id' => $away->id]], []);

        $this->assertSame($away->id, (int) $match->fresh()->match_stats['shootout']['winner_team_id']);
    }

    public function test_a_shootout_won_by_a_team_outside_the_match_is_discarded(): void
    {
        [$save, $home, $away, $match] = $this->world(['round' => 'final']);

        $this->service()->record($save, $match, 0, 0, ['shootout' => ['winner_team_id' => 99999]], []);

        $this->assertArrayNotHasKey('shootout', $match->fresh()->match_stats);
    }

    public function test_guards_detect_a_played_match_and_a_future_week(): void
    {
        [$save, $home, $away, $match] = $this->world();
        $this->assertTrue($this->service()->canStillBeRecorded($save, $match));
        $this->assertFalse($this->service()->isScheduledForFutureWeek($save, $match));

        $this->service()->record($save, $match, 1, 0, null, []);
        $this->assertFalse($this->service()->canStillBeRecorded($save->fresh(), $match->fresh()));

        $future = $this->makeMatch($save, $home, $away, ['week' => 5]);
        $this->assertTrue($this->service()->isScheduledForFutureWeek($save->fresh(), $future));
    }
}

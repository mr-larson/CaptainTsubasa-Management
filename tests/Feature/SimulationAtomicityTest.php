<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameMatch;
use App\Services\DeclarationService;
use App\Services\MatchSimulator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Une série de matchs simulés est écrite tout ou rien : une erreur sur le
 * second match annule aussi le premier, sans dépendre d'un verrou de l'appelant.
 */
class SimulationAtomicityTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_failure_on_a_later_match_rolls_back_the_earlier_ones(): void
    {
        $save = $this->makeSave();
        [$a] = $this->makeTeamWithSquad($save);
        [$b] = $this->makeTeamWithSquad($save);
        [$c] = $this->makeTeamWithSquad($save);
        [$d] = $this->makeTeamWithSquad($save);
        $first  = $this->makeMatch($save, $a, $b);
        $second = $this->makeMatch($save, $c, $d);

        $this->app->instance(DeclarationService::class, new class extends DeclarationService {
            private int $calls = 0;

            public function consumeMatchBoosts($gameSave, int $matchId): void
            {
                if (++$this->calls === 2) {
                    throw new \RuntimeException('boom');
                }
            }
        });

        try {
            app(MatchSimulator::class)->simulateMatchesCollection(
                GameMatch::whereIn('id', [$first->id, $second->id])->orderBy('id')->get(),
                $save,
            );
            $this->fail('Exception attendue');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        foreach ([$first, $second] as $match) {
            $this->assertSame(\App\Enums\MatchStatus::Scheduled, $match->fresh()->status);
            $this->assertNull($match->fresh()->home_score);
        }
        $this->assertSame(0, (int) $a->fresh()->goals_for + (int) $b->fresh()->goals_for);
        $this->assertSame(0, \App\Models\GameSaves\GameMatchPlayerStat::count());
    }
}

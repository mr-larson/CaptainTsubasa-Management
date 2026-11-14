<?php

namespace Tests\Feature;

use App\Contracts\Randomizer;
use App\Models\GameSaves\GameMatch;
use App\Services\Matches\MatchSimulator;
use App\Support\Random\SeededRandomizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Le simulateur tire tout son aléatoire du Randomizer injecté : avec une
 * graine fixée, un match se rejoue à l'identique.
 */
class SeededReplayTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function playWithSeed(int $matchId, int $seed): array
    {
        DB::beginTransaction();
        $this->app->instance(Randomizer::class, new SeededRandomizer($seed));

        app(MatchSimulator::class)->simulateMatchesCollection(GameMatch::whereKey($matchId)->get());

        $match  = GameMatch::find($matchId);
        $result = [
            'score'  => [$match->home_score, $match->away_score],
            'events' => $match->eventList(),
        ];
        DB::rollBack(); // état initial restauré pour pouvoir rejouer

        return $result;
    }

    public function test_same_seed_replays_the_same_match(): void
    {
        $save = $this->makeSave();
        [$home] = $this->makeTeamWithSquad($save);
        [$away] = $this->makeTeamWithSquad($save);
        $match  = $this->makeMatch($save, $home, $away);

        $first  = $this->playWithSeed($match->id, 4242);
        $second = $this->playWithSeed($match->id, 4242);

        $this->assertNotEmpty($first['events']);
        $this->assertEquals($first, $second);
    }

    public function test_different_seeds_diverge(): void
    {
        $save = $this->makeSave();
        [$home] = $this->makeTeamWithSquad($save);
        [$away] = $this->makeTeamWithSquad($save);
        $match  = $this->makeMatch($save, $home, $away);

        $runs = array_map(fn ($seed) => $this->playWithSeed($match->id, $seed), range(1, 6));

        $this->assertGreaterThan(1, count(array_unique(array_map('serialize', $runs))));
    }

    public function test_seeded_randomizer_is_deterministic_and_in_range(): void
    {
        $a = new SeededRandomizer(99);
        $b = new SeededRandomizer(99);

        $seqA = array_map(fn () => $a->int(1, 20), range(1, 50));
        $seqB = array_map(fn () => $b->int(1, 20), range(1, 50));

        $this->assertSame($seqA, $seqB);
        $this->assertGreaterThanOrEqual(1, min($seqA));
        $this->assertLessThanOrEqual(20, max($seqA));
        $this->assertSame((new SeededRandomizer(5))->shuffle(range(1, 10)), (new SeededRandomizer(5))->shuffle(range(1, 10)));
        $this->assertEqualsCanonicalizing(range(1, 10), (new SeededRandomizer(5))->shuffle(range(1, 10)));
    }
}

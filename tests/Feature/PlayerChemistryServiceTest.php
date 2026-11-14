<?php

namespace Tests\Feature;

use App\Models\GameSaves\GamePlayerChemistry;
use App\Services\Matches\MatchLineupSnapshot;
use App\Services\Matches\MatchSimulator;
use App\Services\Players\PlayerChemistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class PlayerChemistryServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_progress_counts_shared_turns_and_line_distance_once_per_match(): void
    {
        $save = $this->makeSave();
        [$home, $players] = $this->makeTeamWithSquad($save, ['formation' => '4-2-2-2'], 12);
        [$away] = $this->makeTeamWithSquad($save);
        $match = $this->makeMatch($save, $home, $away, ['status' => 'played']);
        $match->update(['match_stats' => [
            'lineups' => app(MatchLineupSnapshot::class)->build($match),
            'presence_turns' => 30,
            'presence' => ['home' => [
                ['id' => $players[1]->id, 'slot' => 2, 'from' => 0, 'to' => 30],
                ['id' => $players[2]->id, 'slot' => 3, 'from' => 0, 'to' => 30],
                ['id' => $players[5]->id, 'slot' => 6, 'from' => 0, 'to' => 15],
                ['id' => $players[11]->id, 'slot' => 6, 'from' => 15, 'to' => 30],
                ['id' => $players[10]->id, 'slot' => 11, 'from' => 0, 'to' => 30],
            ]],
        ]]);
        $service = app(PlayerChemistryService::class);
        $service->recordMatch($save, $match);
        $service->recordMatch($save, $match);

        $summary = collect($service->summary($save, $players)[$players[1]->id])->keyBy('partner_id');
        $this->assertSame(10, $summary[$players[2]->id]['score']);
        $this->assertSame(4, $summary[$players[5]->id]['score']);
        $this->assertSame(1, $summary[$players[11]->id]['shared_matches']);
        $this->assertArrayNotHasKey($players[10]->id, $summary->all());
        $this->assertDatabaseMissing('game_player_chemistries', [
            'player_a_id' => $players[5]->id, 'player_b_id' => $players[11]->id,
        ]);
    }

    public function test_simulated_matches_also_build_chemistry_for_their_starters(): void
    {
        $save = $this->makeSave();
        [$home, $players] = $this->makeTeamWithSquad($save);
        [$away] = $this->makeTeamWithSquad($save);
        $match = $this->makeMatch($save, $home, $away);

        app(MatchSimulator::class)->simulateMatchesCollection(collect([$match]), $save);

        $this->assertDatabaseHas('game_player_chemistries', [
            'game_save_id' => $save->id,
            'player_a_id' => $players[1]->id,
            'player_b_id' => $players[2]->id,
            'score' => 10,
            'shared_matches' => 1,
        ]);
        $this->assertTrue($match->fresh()->match_stats['chemistry_recorded']);
    }

    public function test_failed_attempt_waits_for_three_more_shared_matches(): void
    {
        $save = $this->makeSave();
        [$team, $players] = $this->makeTeamWithSquad($save);
        [$a, $b] = $players->slice(1, 2)->values()->all();
        GamePlayerChemistry::insert([
            'game_save_id' => $save->id, 'player_a_id' => $a->id, 'player_b_id' => $b->id,
            'score' => 60, 'shared_matches' => 6,
        ]);
        $service = new class extends PlayerChemistryService {
            public int $nextRoll = 41;

            protected function roll(): int
            {
                return $this->nextRoll;
            }
        };

        $this->assertFalse($service->attempt($save, $team->id, $b->id, $a->id));
        $this->assertSame(3, $service->summary($save, $players)[$a->id][0]['retry_matches']);
        try {
            $service->attempt($save, $team->id, $a->id, $b->id);
            $this->fail('Une deuxième tentative immédiate doit être refusée.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('chemistry', $exception->errors());
        }

        GamePlayerChemistry::query()->update(['score' => 90, 'shared_matches' => 9]);
        $service->nextRoll = 1;
        $this->assertTrue($service->attempt($save, $team->id, $a->id, $b->id));
        $this->assertSame($b->id, $service->confirmedPartners($save, [$a->id, $b->id])[$a->id][0]['id']);
    }
}

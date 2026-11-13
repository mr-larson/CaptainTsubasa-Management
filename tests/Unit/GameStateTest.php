<?php

namespace Tests\Unit;

use App\Models\GameSaves\GameSave;
use App\Support\GameState;
use Tests\TestCase;

class GameStateTest extends TestCase
{
    private function state(array $raw = []): GameState
    {
        return (new GameSave(['state' => $raw]))->gameState();
    }

    public function test_lineup_accessors_keep_the_stored_shape(): void
    {
        $state = $this->state();

        $this->assertNull($state->lineupSlots(7));

        $state->setLineup(7, ['formation' => '4-4-2', 'slots' => [1 => 10, 2 => 11]]);
        $state->setLineupSlots(7, [1 => 10, 2 => null]);

        $this->assertSame(
            [7 => ['formation' => '4-4-2', 'slots' => [1 => 10, 2 => null]]],
            $state->raw()['lineup'],
        );
    }

    public function test_replace_and_remove_in_lineup_only_touch_that_team(): void
    {
        $state = $this->state(['lineup' => [
            1 => ['slots' => [1 => 10, 2 => 11]],
            2 => ['slots' => [1 => 10]],
        ]]);

        $state->replaceInLineup(1, 10, 99);
        $state->removeFromLineup(1, 11);

        $this->assertSame([1 => 99, 2 => null], $state->lineupSlots(1));
        $this->assertSame([1 => 10], $state->lineupSlots(2));
    }

    public function test_ai_rotation_is_only_returned_for_the_current_week(): void
    {
        $state = $this->state();
        $state->setAiRotation(3, 2, 5, [42 => 'MID']);

        $this->assertSame([42 => 'MID'], $state->restingRoles(3, 2, 5));
        $this->assertSame([], $state->restingRoles(3, 2, 6));
        $this->assertSame([], $state->restingRoles(3, 1, 5));
    }

    public function test_midseason_break_lifecycle(): void
    {
        $state = $this->state();

        $this->assertFalse($state->hasPendingMidseasonBreak(8));
        $state->scheduleMidseasonBreak(8);
        $state->scheduleMidseasonBreak(9);   // déjà planifiée : inchangée
        $this->assertTrue($state->hasPendingMidseasonBreak(8));
        $this->assertFalse($state->hasPendingMidseasonBreak(7));

        $state->completeMidseasonBreak();
        $this->assertFalse($state->hasPendingMidseasonBreak(8));
        $this->assertSame(['return_week' => 8, 'completed' => true], $state->midseasonBreak());
    }

    public function test_recap_signings_and_boosts(): void
    {
        $state = $this->state();

        $state->recordSeasonRecap(2, ['champion' => 5]);
        $this->assertSame(['champion' => 5], $state->lastSeasonRecap());
        $this->assertSame([2 => ['champion' => 5]], $state->raw()['season_history']);

        $state->recordFreeAgentSigning(['player_id' => 1]);
        $state->recordFreeAgentSigning(['player_id' => 2]);
        $this->assertCount(2, $state->raw()['free_agent_signings']);
        $state->forgetFreeAgentSignings();
        $this->assertArrayNotHasKey('free_agent_signings', $state->raw());

        $state->addDeclarationBoost(9, 4, 77, 3);
        $this->assertSame([77 => 3], $state->declarationBoosts(9, 4));
        $this->assertTrue($state->consumeDeclarationBoosts(9));
        $this->assertFalse($state->consumeDeclarationBoosts(9));
        $this->assertSame([], $state->declarationBoosts(9, 4));
    }

    public function test_server_only_keys_are_stripped_from_serialization(): void
    {
        $save = new GameSave(['state' => ['shop' => [1], 'ai_training_history' => [2], 'lineup' => [3]]]);

        $this->assertSame(['lineup' => [3]], $save->toArray()['state']);
    }

    public function test_draft_lifecycle_and_snake_order(): void
    {
        $state = $this->state();

        $this->assertFalse($state->hasDraft());
        $this->assertNull($state->currentDraftTeamId());
        $this->assertFalse($state->draftCompleted());
        $this->assertSame(1, $state->draftRound());

        $state->startDraft([3, 5, 9], [3 => 8000, 5 => 8000, 9 => 10000]);
        $this->assertSame([3 => 8000, 5 => 8000, 9 => 10000], $state->draftBudgets());
        $this->assertSame('progressive', $state->draftPricing());
        $this->assertSame(3, $state->currentDraftTeamId());

        // Tour pair : ordre inversé.
        $draft = $state->draft();
        $draft['round'] = 2;
        $draft['current_pick_index'] = 0;
        $state->putDraft($draft);
        $this->assertSame(9, $state->currentDraftTeamId());
        $this->assertSame(2, $state->draftRound());

        $draft['completed'] = true;
        $state->putDraft($draft);
        $this->assertTrue($state->draftCompleted());
        $this->assertNull($state->currentDraftTeamId());

        $state->forgetDraft();
        $this->assertFalse($state->hasDraft());
    }

    public function test_challenges_accessors(): void
    {
        $state = $this->state();

        $this->assertNull($state->challenges());
        $this->assertSame(0, $state->challengesSeason());
        $this->assertSame([], $state->challengeTeams());
        $this->assertNull($state->challengeEntry(4));

        $state->startChallenges(2, '2026-11-13T10:00:00+00:00', [4 => ['expected_rank' => 3]]);

        $this->assertSame(2, $state->challengesSeason());
        $this->assertSame(['expected_rank' => 3], $state->challengeEntry(4));
        $this->assertSame(['expected_rank' => 3], $state->challengeEntry(4));
        $this->assertNull($state->challengeEntry(5));

        $data = $state->challenges();
        $data['teams'][4]['paid_rewards']['k'] = 100;
        $state->putChallenges($data);
        $this->assertSame(100, $state->challengeEntry(4)['paid_rewards']['k']);
    }
}

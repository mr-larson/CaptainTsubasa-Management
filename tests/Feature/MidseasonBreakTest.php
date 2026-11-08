<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSanction;
use App\Services\FoulAndInjuryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class MidseasonBreakTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_midseason_break_recovers_stamina_once_and_resets_only_the_yellow_card_cycle(): void
    {
        $save = $this->makeSave(['week' => 3]);
        $save->setConfig(['bonus_cards_enabled' => false, 'ai_training_enabled' => false, 'ai_transfers_enabled' => false]);
        $human = $this->makeTeam($save, ['is_controlled' => true]);
        $ai = $this->makeTeam($save);
        $this->makeTeam($save);
        $this->makeTeam($save);
        $save->update(['controlled_game_team_id' => $human->id]);
        $player = $this->makePlayer($save, ['stamina' => 55]);
        $contract = $this->makeContract($save, $human, $player, ['end_week' => 4]);
        $opponent = $this->makePlayer($save, ['stamina' => 98]);
        $this->makeContract($save, $ai, $opponent);
        $freePlayer = $this->makePlayer($save, ['stamina' => 30]);
        $last = $this->makeMatch($save, $human, $ai, ['week' => 3]);
        $next = $this->makeMatch($save, $ai, $human, ['week' => 4]);
        $card = ['type' => 'card', 'card_type' => 'yellow', 'player_id' => $player->id];
        $red = ['type' => 'card', 'card_type' => 'red', 'player_id' => $opponent->id];
        $play = route('game-saves.Play', $save);

        $this->actingAs($save->user)->post(route('game-saves.matches.finish', [$save, $last]), [
            'scoresByTeamId' => [$human->id => 0, $ai->id => 0],
            'foulEvents' => [$card, $card, $red],
        ])->assertRedirect($play);

        $save->refresh();
        $this->assertTrue($save->hasPendingMidseasonBreak());
        $this->assertSame(2, GameSanction::currentYellowCardCycle($save)->count());
        $this->get(route('game-saves.match', $save))->assertRedirect($play);
        $this->post(route('game-saves.simulate-week', $save), ['week' => 4])->assertRedirect($play);
        $this->assertTrue($save->fresh()->hasPendingMidseasonBreak());

        $budget = $human->fresh()->budget;
        $stamina = $player->fresh()->stamina;
        $freeStamina = $freePlayer->fresh()->stamina;
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->post(route('game-saves.simulate-week', $save), [
                'week' => 4, 'midseason_break' => true,
            ])->assertRedirect($play);
        }

        $save->refresh();
        $this->assertFalse($save->hasPendingMidseasonBreak());
        $this->assertSame(4, (int) $save->week);
        $this->assertTrue($contract->fresh()->isActive(4));
        $this->assertEquals($budget, $human->fresh()->budget);
        $this->assertEquals($stamina + 20, $player->fresh()->stamina);
        $this->assertEquals($freeStamina + 20, $freePlayer->fresh()->stamina);
        $this->assertEquals(100, $opponent->fresh()->stamina);
        $this->assertSame('scheduled', $next->fresh()->status);
        $this->assertSame(0, GameSanction::currentYellowCardCycle($save)->count());
        $this->assertSame(3, GameSanction::where('game_save_id', $save->id)->count());
        $this->assertTrue(GameSanction::where('type', 'red')->firstOrFail()->isActive(4));

        app(FoulAndInjuryService::class)->processMatchEvents($save, $next, [$card]);
        $newYellow = GameSanction::currentYellowCardCycle($save)->sole();
        $this->assertSame('yellow', $newYellow->type);
        $this->assertSame(1, $newYellow->yellow_card_count);
    }
}

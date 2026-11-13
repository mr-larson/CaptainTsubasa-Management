<?php

namespace Tests\Feature;

use App\Enums\PromiseStatus;
use App\Services\PromiseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class GameContractRenewalTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_renewal_uses_current_stats_and_cannot_go_beyond_the_season_or_charge_twice(): void
    {
        $save = $this->makeSave(['week' => 5]);
        $team = $this->makeTeam($save, ['budget' => 3000]);
        $save->update(['controlled_game_team_id' => $team->id]);
        foreach (range(1, 14) as $i) {
            $this->makeTeam($save);
        }
        $player = $this->makePlayer($save);
        $player->number = 11;
        $player->save();
        $contract = $this->makeContract($save, $team, $player, [
            'end_week' => 5,
            'is_captain' => true,
        ]);
        $promises = app(PromiseService::class);
        $promise = $promises->create($save, $player, $team->id, 'renewal');

        // Le tir augmente après la signature initiale. Moyenne des stats de champ : 60.
        $player->update(['shot' => 100]);
        $url = route('game-saves.contracts.renew', [$save, $contract]);
        $this->actingAs($save->user);

        $this->patch($url, ['end_week' => 5, 'weeks' => 26])->assertSessionHasErrors('weeks');
        $this->assertSame(5, $contract->fresh()->end_week);
        $this->assertSame(PromiseStatus::Pending, $promise->fresh()->status);

        $this->patch($url, ['end_week' => 5, 'weeks' => 1])->assertSessionHasNoErrors();
        $this->assertSame(PromiseStatus::Kept, $promise->fresh()->status);
        $this->assertEquals(75, $player->fresh()->morale);
        $this->assertEquals(15, $player->fresh()->coach_affinity);
        $this->assertSame(6, $contract->fresh()->end_week);
        $this->assertSame(83, $contract->fresh()->salary);
        $this->assertEquals(2917, $team->fresh()->budget);

        $this->patch($url, ['end_week' => 5, 'weeks' => 1])->assertSessionHasErrors('weeks');
        $this->assertEquals(2917, $team->fresh()->budget);

        $this->patch($url, ['end_week' => 6, 'weeks' => 24])->assertSessionHasNoErrors();
        $this->assertSame(30, $contract->fresh()->end_week);
        $this->assertTrue($contract->fresh()->is_captain);
        $this->assertEquals(11, $player->fresh()->number);
        $this->assertEquals(925, $team->fresh()->budget);
        $this->patch($url, ['end_week' => 30, 'weeks' => 1])->assertSessionHasErrors('weeks');

        $promises->evaluateForWeek($save, $promise->due_week);
        $this->assertSame(PromiseStatus::Kept, $promise->fresh()->status);
        $this->assertEquals(75, $player->fresh()->morale);
        $this->assertEquals(15, $player->fresh()->coach_affinity);
        $this->assertDatabaseHas('game_player_morale_logs', [
            'game_player_id' => $player->id,
            'source' => 'promise',
            'value' => 15,
            'week' => 5,
        ]);
    }
}

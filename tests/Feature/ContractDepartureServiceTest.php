<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameContract;
use App\Services\ContractDepartureService;
use App\Services\PromiseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class ContractDepartureServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_risk_rises_with_either_indicator_and_is_never_certain(): void
    {
        $this->assertSame(0, ContractDepartureService::chance(31, -49));
        $this->assertSame(5, ContractDepartureService::chance(30, 0));
        $this->assertSame(5, ContractDepartureService::chance(80, -50));
        $this->assertSame(25, ContractDepartureService::chance(15, -75));
        $this->assertSame(45, ContractDepartureService::chance(0, -100));
    }

    public function test_human_and_ai_players_can_leave_and_unsuccessful_rolls_retry_in_two_weeks(): void
    {
        $save = $this->makeSave(['week' => 2]);
        $human = $this->makeTeam($save, ['is_controlled' => true]);
        $ai = $this->makeTeam($save);
        $unhappy = $this->makePlayer($save, ['morale' => 15]);
        $angry = $this->makePlayer($save, ['coach_affinity' => -80]);
        $stays = $this->makePlayer($save, ['morale' => 30]);
        $humanContract = $this->makeContract($save, $human, $unhappy);
        $aiContract = $this->makeContract($save, $ai, $angry);
        $staysContract = $this->makeContract($save, $ai, $stays);
        $promise = app(PromiseService::class)->create($save, $unhappy, $human->id, 'starter');
        $save->update(['state' => ['lineup' => [$human->id => ['slots' => [1 => $unhappy->id]]]]]);

        $service = new class extends ContractDepartureService {
            public array $rolls = [1, 1, 100, 1];

            protected function roll(): int
            {
                return array_shift($this->rolls);
            }
        };

        $service->resolveForWeek($save, 2);
        $this->assertSame(2, $humanContract->fresh()->end_week);
        $this->assertSame(2, $aiContract->fresh()->end_week);
        $this->assertNull($staysContract->fresh()->end_week);
        $this->assertTrue($humanContract->fresh()->isActive(2));
        $this->assertFalse(GameContract::where('game_player_id', $unhappy->id)->activeAt(3)->exists());
        $this->assertNull($save->state['lineup'][$human->id]['slots'][1]);
        $this->assertSame('expired', $promise->fresh()->status);
        $this->assertCount(2, $save->state['contract_departures']['entries']);

        $service->resolveForWeek($save->fresh(), 2);
        $service->resolveForWeek($save->fresh(), 3);
        $this->assertSame([1], $service->rolls);
        $service->resolveForWeek($save->fresh(), 4);
        $this->assertSame(4, $staysContract->fresh()->end_week);
    }
}

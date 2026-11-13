<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameContract;
use App\Services\FreeAgentService;
use App\Support\ActionOutcome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Règles de recrutement d'un agent libre, testées directement sur le service
 * (sans passer par la route) : ordre des refus, signature, débit, numéro de maillot.
 */
class FreeAgentServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function service(): FreeAgentService
    {
        return app(FreeAgentService::class);
    }

    /** Une partie de 4 équipes (saison de 6 semaines) avec une équipe acheteuse riche. */
    private function world(int $budget = 1_000_000): array
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['budget' => $budget]);
        foreach (range(1, 3) as $_) {
            $this->makeTeam($save);
        }

        return [$save->fresh(), $team, $this->makePlayer($save)];
    }

    public function test_it_refuses_signings_in_world_cup(): void
    {
        [$save, $team, $player] = $this->world();
        $save->update(['competition_type' => 'world_cup']);

        $outcome = $this->service()->sign($save->fresh(), $player, $team, 2, null);

        $this->assertTrue($outcome->isError());
        $this->assertSame('player', $outcome->field);
    }

    public function test_it_refuses_a_duration_longer_than_the_remaining_weeks(): void
    {
        [$save, $team, $player] = $this->world();

        $outcome = $this->service()->sign($save, $player, $team, 99, null);

        $this->assertTrue($outcome->isError());
        $this->assertSame('matches_total', $outcome->field);
        $this->assertSame(0, GameContract::where('game_player_id', $player->id)->count());
    }

    public function test_it_says_so_without_error_when_the_player_already_has_a_contract(): void
    {
        [$save, $team, $player] = $this->world();
        $this->makeContract($save, $team, $player, ['end_week' => 6]);

        $outcome = $this->service()->sign($save, $player, $team, 2, null);

        $this->assertSame(ActionOutcome::INFO, $outcome->level);
    }

    public function test_it_refuses_when_the_budget_is_too_low_and_changes_nothing(): void
    {
        [$save, $team, $player] = $this->world(budget: 1);

        $outcome = $this->service()->sign($save, $player, $team, 2, null);

        $this->assertTrue($outcome->isError());
        $this->assertSame('contract', $outcome->field);
        $this->assertSame(1, (int) $team->fresh()->budget);
        $this->assertSame(0, GameContract::where('game_player_id', $player->id)->count());
    }

    public function test_it_signs_debits_the_budget_and_gives_the_first_free_jersey_number(): void
    {
        [$save, $team, $player] = $this->world();
        // Les numéros 1 et 2 sont déjà pris dans l'équipe.
        foreach ([1, 2] as $n) {
            $teammate = $this->makePlayer($save);
            $teammate->number = $n; // « number » n'est pas assignable en masse
            $teammate->save();
            $this->makeContract($save, $team, $teammate);
        }
        $budget = (int) $team->fresh()->budget;

        $outcome = $this->service()->sign($save->fresh(), $player, $team->fresh(), 3, 'Renfort offensif');

        $this->assertSame(ActionOutcome::SUCCESS, $outcome->level);

        $contract = GameContract::where('game_player_id', $player->id)->firstOrFail();
        $this->assertSame(3, (int) $player->fresh()->number);
        $this->assertSame(1, (int) $contract->start_week);
        $this->assertSame(3, (int) $contract->end_week); // semaines 1, 2 et 3
        $this->assertSame($budget - (int) $contract->salary * 3, (int) $team->fresh()->budget);
    }
}

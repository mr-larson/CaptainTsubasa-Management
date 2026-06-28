<?php

namespace Tests\Feature;

use App\Services\StaminaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Endurance hebdomadaire (StaminaService) : les joueurs ayant disputé un match
 * perdent de l'endurance, les autres récupèrent, le tout borné à [0, 100].
 * Source des participants : match_stats, repli sur les titulaires.
 */
class StaminaServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_players_who_played_lose_stamina_others_recover(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $played = $this->makePlayer($save, ['stamina' => 50]);
        $rested = $this->makePlayer($save, ['stamina' => 50]);
        $this->makeContract($save, $team, $played);
        $this->makeContract($save, $team, $rested, ['is_starter' => false]);

        // Source primaire : match_stats.players → seul "played" a joué.
        $this->makeMatch($save, $team, $team, [
            'week' => 1, 'status' => 'played',
            'match_stats' => ['players' => [(string) $played->id => []]],
        ]);

        StaminaService::applyAfterWeek($save, 1);

        $this->assertSame(45, (int) $played->fresh()->stamina); // -5 par défaut
        $this->assertSame(60, (int) $rested->fresh()->stamina); // +10 par défaut
    }

    public function test_fallback_uses_starters_when_no_match_stats(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $home = $this->makeTeam($save);
        $away = $this->makeTeam($save);
        $starter = $this->makePlayer($save, ['stamina' => 50]);
        $sub     = $this->makePlayer($save, ['stamina' => 50]);
        $this->makeContract($save, $home, $starter, ['is_starter' => true]);
        $this->makeContract($save, $home, $sub, ['is_starter' => false]);

        // Pas de match_stats → repli sur les titulaires des deux équipes.
        $this->makeMatch($save, $home, $away, ['week' => 1, 'status' => 'played']);

        StaminaService::applyAfterWeek($save, 1);

        $this->assertSame(45, (int) $starter->fresh()->stamina); // titulaire → a joué
        $this->assertSame(60, (int) $sub->fresh()->stamina);     // remplaçant → repos
    }

    public function test_stamina_is_clamped_to_bounds(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $low  = $this->makePlayer($save, ['stamina' => 3]);   // jouera → ne descend pas sous 0
        $high = $this->makePlayer($save, ['stamina' => 95]);  // repos → plafonné à 100
        $this->makeContract($save, $team, $low);
        $this->makeContract($save, $team, $high, ['is_starter' => false]);

        $this->makeMatch($save, $team, $team, [
            'week' => 1, 'status' => 'played',
            'match_stats' => ['players' => [(string) $low->id => []]],
        ]);

        StaminaService::applyAfterWeek($save, 1);

        $this->assertSame(0, (int) $low->fresh()->stamina);
        $this->assertSame(100, (int) $high->fresh()->stamina);
    }

    public function test_config_overrides_cost_and_recovery(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $save->setConfig(['match_stamina_cost' => 20, 'rest_stamina_recovery' => 30]);
        $team = $this->makeTeam($save);
        $played = $this->makePlayer($save, ['stamina' => 50]);
        $rested = $this->makePlayer($save, ['stamina' => 50]);
        $this->makeContract($save, $team, $played);
        $this->makeContract($save, $team, $rested, ['is_starter' => false]);

        $this->makeMatch($save, $team, $team, [
            'week' => 1, 'status' => 'played',
            'match_stats' => ['players' => [(string) $played->id => []]],
        ]);

        StaminaService::applyAfterWeek($save->fresh(), 1);

        $this->assertSame(30, (int) $played->fresh()->stamina); // 50 - 20
        $this->assertSame(80, (int) $rested->fresh()->stamina); // 50 + 30
    }

    public function test_apply_after_match_handles_a_single_match(): void
    {
        $save = $this->makeSave(['week' => 1]);
        $team = $this->makeTeam($save);
        $played = $this->makePlayer($save, ['stamina' => 50]);
        $this->makeContract($save, $team, $played);

        $match = $this->makeMatch($save, $team, $team, [
            'week' => 1, 'status' => 'played',
            'match_stats' => ['players' => [(string) $played->id => []]],
        ]);

        StaminaService::applyAfterMatch($save, $match);

        $this->assertSame(45, (int) $played->fresh()->stamina);
    }
}

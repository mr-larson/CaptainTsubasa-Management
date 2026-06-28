<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameBonusCard;
use App\Services\AiBonusCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * IA d'achat de cartes (AiBonusCardService::processWeek) : ne fait rien tant
 * que la boutique de la semaine n'est pas générée, puis achète pour les équipes
 * IA les offres dont le score dépasse le seuil, en gardant une réserve budget.
 */
class AiBonusCardServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private AiBonusCardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AiBonusCardService::class);
    }

    public function test_noop_when_shop_not_generated(): void
    {
        $save = $this->makeSave(['week' => 1, 'season' => 1]);
        $this->makeTeam($save);

        $this->service->processWeek($save);

        $this->assertSame(0, GameBonusCard::where('game_save_id', $save->id)->count());
    }

    public function test_noop_when_shop_is_for_another_week(): void
    {
        $save = $this->makeSave(['week' => 2, 'season' => 1]);
        $team = $this->makeTeam($save);
        $card = $this->makeBonusCard(['effect_type' => 'stat_boost', 'execution_phase' => 'pre_match']);

        // Boutique d'une AUTRE semaine (1 ≠ 2) → ignorée.
        $save->state = ['shop' => [
            'season' => 1, 'week' => 1,
            'offers_by_team' => [(string) $team->id => [[
                'bonus_card_id' => $card->id, 'tier' => 'bronze', 'cost' => 100,
                'effect_type' => 'stat_boost', 'execution_phase' => 'pre_match', 'target' => 'self',
            ]]],
        ]];
        $save->save();

        $this->service->processWeek($save);

        $this->assertSame(0, GameBonusCard::where('game_save_id', $save->id)->count());
    }

    public function test_ai_team_buys_an_affordable_well_scored_offer(): void
    {
        $save = $this->makeSave(['week' => 1, 'season' => 1]);
        $team = $this->makeTeam($save, ['budget' => 5000]);
        $card = $this->makeBonusCard(['effect_type' => 'stat_boost', 'execution_phase' => 'pre_match', 'tier' => 'bronze', 'cost' => 100]);

        $save->state = ['shop' => [
            'season' => 1, 'week' => 1,
            'offers_by_team' => [(string) $team->id => [[
                'bonus_card_id' => $card->id, 'tier' => 'bronze', 'cost' => 100,
                'effect_type' => 'stat_boost', 'execution_phase' => 'pre_match', 'target' => 'self',
            ]]],
        ]];
        $save->save();

        $this->service->processWeek($save);

        // Carte achetée (stat_boost score 60 ≥ seuil bronze 35), budget débité, réserve préservée.
        $this->assertSame(1, GameBonusCard::where('game_team_id', $team->id)->count());
        $this->assertSame(4900, (int) $team->fresh()->budget);
    }

    public function test_offer_above_budget_minus_reserve_is_skipped(): void
    {
        $save = $this->makeSave(['week' => 1, 'season' => 1]);
        // Budget tout juste insuffisant : 550 < coût (100) + réserve (500).
        $team = $this->makeTeam($save, ['budget' => 550]);
        $card = $this->makeBonusCard(['effect_type' => 'stat_boost', 'execution_phase' => 'pre_match', 'cost' => 100]);

        $save->state = ['shop' => [
            'season' => 1, 'week' => 1,
            'offers_by_team' => [(string) $team->id => [[
                'bonus_card_id' => $card->id, 'tier' => 'bronze', 'cost' => 100,
                'effect_type' => 'stat_boost', 'execution_phase' => 'pre_match', 'target' => 'self',
            ]]],
        ]];
        $save->save();

        $this->service->processWeek($save);

        $this->assertSame(0, GameBonusCard::where('game_team_id', $team->id)->count(), 'La réserve budget bloque l\'achat.');
    }
}

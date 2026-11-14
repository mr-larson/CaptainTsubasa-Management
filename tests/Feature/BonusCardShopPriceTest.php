<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameBonusCard;
use App\Services\Cards\BonusCardShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class BonusCardShopPriceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_existing_offer_keeps_its_card_but_uses_the_common_price(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['is_controlled' => true, 'budget' => 250]);
        $card = $this->makeBonusCard(['tier' => 'gold', 'cost' => 900]);
        $save->update([
            'controlled_game_team_id' => $team->id,
            'state' => ['shop' => [
                'season' => 1, 'week' => 1,
                'offers_by_team' => [$team->id => [[
                    'bonus_card_id' => $card->id, 'name' => $card->name, 'tier' => 'gold', 'cost' => 900,
                ]]],
            ]],
        ]);

        $offers = app(BonusCardShopService::class)->getOffersForTeam($save, $team->id);
        $this->assertSame($card->id, $offers[0]['bonus_card_id']);
        $this->assertSame(200, $offers[0]['cost']);

        $this->actingAs($save->user)->post(route('game-saves.bonus-cards.buy', $save), [
            'bonus_card_id' => $card->id, 'tier' => 'gold',
        ])->assertSessionHasNoErrors();

        $this->assertSame(50, (int) $team->fresh()->budget);
        $this->assertSame(200, (int) GameBonusCard::where('game_team_id', $team->id)->firstOrFail()->cost_paid);
    }
}

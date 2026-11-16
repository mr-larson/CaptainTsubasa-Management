<?php

namespace Tests\Feature;

use App\Models\BonusCard;
use App\Models\GameSaves\GameBonusCard;
use App\Services\Cards\BonusCardActivationService;
use App\Services\Cards\BonusCardShopService;
use App\Services\Cards\SponsorChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Lot 2 des cartes « remontada » : carte gratuite hebdomadaire des 3 derniers, « Piège » (malus
 * renvoyé), « Robin des Bois » (budget pris au leader) et « Prime de l'exploit ».
 */
class ComebackItemBoxTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    /** Ligue classée du 1er au dernier selon les victoires données. @return array{0: \App\Models\GameSaves\GameSave, 1: array} */
    private function league(array $wins): array
    {
        $save  = $this->makeSave();
        $teams = [];
        foreach ($wins as $i => $w) {
            $teams[] = $this->makeTeam($save, ['wins' => $w, 'budget' => 1000, 'name' => 'Club '.($i + 1)]);
        }

        return [$save->fresh(), $teams];
    }

    private function malus(string $effect, array $value): BonusCard
    {
        return $this->makeBonusCard(['kind' => 'malus', 'target' => 'team', 'effect_type' => $effect, 'effect_value' => $value]);
    }

    public function test_the_bottom_three_get_one_free_card_a_week_outside_their_offers(): void
    {
        [$save, $t] = $this->league([5, 4, 3, 1, 0]);
        BonusCard::query()->delete();
        foreach (range(1, 5) as $i) {
            $this->makeBonusCard(['name' => "Carte {$i}"]);
        }

        app(BonusCardShopService::class)->generateWeeklyOffers($save);

        $state = $save->fresh()->gameState();
        $this->assertNull($state->shopFreeCard($t[0]->id));
        $gift = $state->shopFreeCard($t[4]->id);
        $this->assertNotNull($gift);
        $this->assertNotContains($gift['bonus_card_id'], array_column($state->shopOffers($t[4]->id), 'bonus_card_id'));

        // Retirage de la même semaine : pas de seconde carte offerte, et la première reste connue.
        app(BonusCardShopService::class)->generateWeeklyOffers($save->fresh());
        $this->assertSame($gift['bonus_card_id'], $save->fresh()->gameState()->shopFreeCard($t[4]->id)['bonus_card_id']);

        $free = GameBonusCard::where('game_save_id', $save->id)->where('cost_paid', 0)->get();
        $this->assertEqualsCanonicalizing(
            [$t[2]->id, $t[3]->id, $t[4]->id],
            $free->pluck('game_team_id')->map(fn ($id) => (int) $id)->all(),
        );
        $this->assertSame(1000, (int) $t[4]->fresh()->budget, 'La carte offerte ne coûte rien.');

    }

    public function test_no_free_card_while_every_team_is_level_on_points(): void
    {
        [$save] = $this->league([0, 0, 0, 0, 0]);

        app(BonusCardShopService::class)->generateWeeklyOffers($save);

        $this->assertSame(0, GameBonusCard::where('game_save_id', $save->id)->count());
    }

    public function test_a_trap_sends_the_next_malus_back_to_its_sender_once(): void
    {
        [$save, $t] = $this->league([5, 0]);
        $service = app(BonusCardActivationService::class);
        $service->activate($this->makeGameCard($save, $t[0], $this->makeBonusCard(['effect_type' => 'malus_reflect', 'effect_value' => []])), $save);

        $drain = $this->malus('opponent_budget_drain', ['amount' => 100, 'target_mode' => 'leader']);
        $result = $service->activate($this->makeGameCard($save, $t[1], $drain), $save->fresh());

        $this->assertTrue($result['reflected']);
        $this->assertSame(1000, (int) $t[0]->fresh()->budget, 'La cible piégée ne subit rien.');
        $this->assertSame(900, (int) $t[1]->fresh()->budget, "L'expéditeur subit son propre malus.");

        // Le piège est consommé : le malus suivant touche bien la cible.
        $service->activate($this->makeGameCard($save, $t[1], $drain), $save->fresh());
        $this->assertSame(900, (int) $t[0]->fresh()->budget);
    }

    public function test_robin_hood_only_takes_what_the_leader_has(): void
    {
        [$save, $t] = $this->league([5, 0]);
        $t[0]->update(['budget' => 300]);
        $steal = $this->malus('opponent_budget_steal', ['amount' => 500, 'target_mode' => 'leader']);

        app(BonusCardActivationService::class)->activate($this->makeGameCard($save, $t[1], $steal), $save);

        $this->assertSame(0, (int) $t[0]->fresh()->budget);
        $this->assertSame(1300, (int) $t[1]->fresh()->budget);
    }

    public function test_the_upset_challenge_requires_a_better_ranked_next_opponent(): void
    {
        [$save, $t] = $this->league([5, 0]);
        $this->makeMatch($save, $t[0], $t[1]);
        $card = $this->makeBonusCard(['target' => 'finance', 'effect_type' => 'revenue_challenge',
            'effect_value' => ['challenge' => 'upset', 'reward' => 1500]]);
        $service = app(BonusCardActivationService::class);

        $refused = $this->makeGameCard($save, $t[0], $card); // le leader affronte une équipe moins bien classée
        try {
            $service->activate($refused, $save);
            $this->fail('Le défi doit être refusé.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('card', $e->errors());
        }
        $this->assertSame('available', $refused->fresh()->status);

        $service->activate($this->makeGameCard($save, $t[1], $card), $save);
        $pending = $save->fresh()->gameState()->pendingSponsorChallenges();
        $this->assertCount(1, $pending);
        $this->assertSame('upset', $pending[0]['challenge']);

        $this->assertTrue(SponsorChallengeService::evaluateChallenge('upset', 2, 1));
        $this->assertFalse(SponsorChallengeService::evaluateChallenge('upset', 1, 1));
    }
}

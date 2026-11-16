<?php

namespace Tests\Feature;

use App\Models\GameSaves\GamePlayer;
use App\Services\Cards\BonusCardActivationService;
use App\Services\Cards\BonusCardShopService;
use App\Services\Cards\PreMatchCardService;
use Database\Seeders\BonusCardSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Cartes « remontada » : réservées par la boutique au bas du classement, malus visant l'équipe
 * classée juste devant, fatigue limitée à quelques titulaires, boost de l'outsider et récupération.
 */
class ComebackCardsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    /** Ligue de 4 équipes classées 1er → 4e (5, 3, 1 et 0 victoires). @return array{0: \App\Models\GameSaves\GameSave, 1: array} */
    private function league(array $wins = [5, 3, 1, 0]): array
    {
        $save  = $this->makeSave();
        $teams = [];
        foreach ($wins as $i => $w) {
            $teams[] = $this->makeTeam($save, ['wins' => $w, 'budget' => 1000, 'name' => 'Club '.($i + 1)]);
        }

        return [$save->fresh(), $teams];
    }

    /** Noms des cartes proposées à une équipe par la boutique de la semaine. @return string[] */
    private function offerNames($save, $team): array
    {
        return collect($save->fresh()->gameState()->shopOffers($team->id))->pluck('name')->sort()->values()->all();
    }

    /** Catalogue réduit à 3 cartes (celles insérées par les migrations de données sont retirées). */
    private function catalog(): void
    {
        \App\Models\BonusCard::query()->delete();
        $this->makeBonusCard(['name' => 'Normale']);
        $this->makeBonusCard(['name' => 'Derniers', 'tier' => 'gold', 'effect_value' => ['amount' => 10, 'rank_gate' => 'bottom_3']]);
        $this->makeBonusCard(['name' => 'Devant', 'kind' => 'malus', 'target' => 'team',
            'effect_type' => 'opponent_budget_drain', 'effect_value' => ['amount' => 100, 'target_mode' => 'ahead']]);
    }

    public function test_the_shop_reserves_comeback_cards_for_the_bottom_of_the_table(): void
    {
        [$save, $t] = $this->league();
        $this->catalog();

        app(BonusCardShopService::class)->generateWeeklyOffers($save);

        $this->assertSame(['Normale'], $this->offerNames($save, $t[0]), 'Le leader : ni carte réservée, ni carte « équipe de devant ».');
        $this->assertSame(['Devant', 'Normale'], $this->offerNames($save, $t[1]), 'Le 2e (moitié haute) : pas de carte réservée.');
        $this->assertSame(['Derniers', 'Devant', 'Normale'], $this->offerNames($save, $t[3]), 'Le dernier a accès à tout.');
    }

    public function test_no_comeback_card_while_every_team_is_level_on_points(): void
    {
        [$save, $t] = $this->league([0, 0, 0, 0]);
        $this->catalog();

        app(BonusCardShopService::class)->generateWeeklyOffers($save);

        foreach ($t as $team) {
            $this->assertNotContains('Derniers', $this->offerNames($save, $team));
        }
    }

    public function test_an_ahead_malus_hits_the_team_ranked_just_above_and_is_refused_for_the_leader(): void
    {
        [$save, $t] = $this->league();
        $card = $this->makeBonusCard(['kind' => 'malus', 'target' => 'team',
            'effect_type' => 'opponent_budget_drain', 'effect_value' => ['amount' => 100, 'target_mode' => 'ahead']]);

        app(BonusCardActivationService::class)->activate($this->makeGameCard($save, $t[3], $card), $save);

        $this->assertSame(900, (int) $t[2]->fresh()->budget, 'Le 4e vise le 3e.');
        $this->assertSame(1000, (int) $t[0]->fresh()->budget);

        $owned = $this->makeGameCard($save, $t[0], $card);
        try {
            app(BonusCardActivationService::class)->activate($owned, $save);
            $this->fail('Le leader n\'a personne devant lui.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('card', $e->errors());
        }
        $this->assertSame('available', $owned->fresh()->status);
    }

    public function test_partial_stamina_drain_only_tires_three_outfield_starters(): void
    {
        $save = $this->makeSave();
        [$target, $players] = $this->makeTeamWithSquad($save, ['wins' => 3]);
        $sender = $this->makeTeam($save, ['wins' => 0]);
        $card = $this->makeBonusCard(['kind' => 'malus', 'target' => 'team',
            'effect_type' => 'opponent_partial_stamina_drain',
            'effect_value' => ['amount' => 10, 'count' => 3, 'target_mode' => 'ahead']]);

        app(BonusCardActivationService::class)->activate($this->makeGameCard($save, $sender, $card), $save);

        $fresh = GamePlayer::whereIn('id', $players->pluck('id'))->get();
        $this->assertSame(3, $fresh->where('stamina', 90)->count());
        $this->assertSame(8, $fresh->where('stamina', 100)->count());
        $this->assertSame(100, (int) $fresh->firstWhere('position', 'GK')->stamina, 'Le gardien est épargné.');
    }

    public function test_underdog_boost_scales_with_the_points_gap_to_the_next_opponent(): void
    {
        $save = $this->makeSave();
        $me       = $this->makeTeam($save, ['wins' => 1]);              // 3 pts
        $opponent = $this->makeTeam($save, ['wins' => 4, 'draws' => 1]); // 13 pts
        $this->makeMatch($save, $me, $opponent);
        $card = $this->makeBonusCard(['target' => 'match', 'execution_phase' => 'pre_match',
            'effect_type' => 'underdog_stat_boost',
            'effect_value' => ['min' => 3, 'max' => 15, 'boosted_stats' => ['attack', 'defense'], 'rank_gate' => 'bottom_half']]);

        app(BonusCardActivationService::class)->activate($this->makeGameCard($save, $me, $card), $save);
        $this->assertEqualsCanonicalizing(
            ['attack' => 10, 'defense' => 10],
            app(PreMatchCardService::class)->getPreMatchStatBoosts($save->fresh(), $me->id),
        );

        // Adversaire moins bien classé : le plancher s'applique.
        app(BonusCardActivationService::class)->activate($this->makeGameCard($save, $opponent, $card), $save);
        $this->assertEqualsCanonicalizing(
            ['attack' => 3, 'defense' => 3],
            app(PreMatchCardService::class)->getPreMatchStatBoosts($save->fresh(), $opponent->id),
        );
    }

    public function test_team_recovery_restores_stamina_and_morale_and_grants_a_shield(): void
    {
        $save = $this->makeSave();
        [$team, $players] = $this->makeTeamWithSquad($save);
        GamePlayer::whereIn('id', $players->pluck('id'))->update(['stamina' => 40, 'morale' => 30]);
        $card = $this->makeBonusCard(['effect_type' => 'team_recovery',
            'effect_value' => ['stamina' => 100, 'morale' => 100, 'shield' => true]]);

        app(BonusCardActivationService::class)->activate($this->makeGameCard($save, $team, $card), $save);

        $fresh = GamePlayer::whereIn('id', $players->pluck('id'))->get();
        $this->assertSame([100], $fresh->pluck('stamina')->map(fn ($v) => (int) $v)->unique()->values()->all());
        $this->assertSame([100], $fresh->pluck('morale')->map(fn ($v) => (int) $v)->unique()->values()->all());
        $this->assertTrue($save->fresh()->gameState()->consumeMalusShield($team->id));
    }

    public function test_the_same_team_debuff_cannot_be_stacked_on_a_target(): void
    {
        [$save, $t] = $this->league();
        $card = $this->makeBonusCard(['name' => 'Pression', 'kind' => 'malus', 'target' => 'team',
            'effect_type' => 'opponent_team_stat_debuff',
            'effect_value' => ['stats' => ['attack' => 8, 'speed' => 8], 'target_mode' => 'leader']]);

        app(BonusCardActivationService::class)->activate($this->makeGameCard($save, $t[3], $card), $save);

        $second = $this->makeGameCard($save, $t[2], $card);
        try {
            app(BonusCardActivationService::class)->activate($second, $save->fresh());
            $this->fail('Le même debuff ne se cumule pas.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('card', $e->errors());
        }

        $this->assertSame('available', $second->fresh()->status);
        $this->assertEqualsCanonicalizing(
            ['attack' => -8, 'speed' => -8],
            app(PreMatchCardService::class)->getMatchStatModifiers($save->fresh(), $t[0]->id)['team'],
        );
    }

    public function test_the_catalogue_comeback_cards_are_seeded(): void
    {
        $names = collect(BonusCardSeeder::cards())->pluck('name');

        foreach (['Remontada', 'Rage de l\'outsider', 'État de grâce', 'Croc-en-jambe', 'Marquage serré', 'Pression du favori'] as $name) {
            $this->assertContains($name, $names);
        }
    }
}

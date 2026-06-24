<?php

namespace App\Services;

use App\Models\BonusCard;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;

class BonusCardShopService
{
    /**
     * Génère 3 offres par équipe pour la semaine courante.
     * Les offres sont stockées dans state.shop.offers_by_team[team_id].
     * La pondération Bronze/Argent/Or est inversement proportionnelle au classement.
     */
    public function generateWeeklyOffers(GameSave $gameSave): void
    {
        $season = $gameSave->season;
        $week   = $gameSave->week;

        // Charger le classement actuel
        $teams    = $gameSave->gameTeams()->get();
        $standings = $this->buildStandings($gameSave, $teams);
        $total    = $teams->count();

        // Charger le catalogue, séparé bonus / malus
        $allCards   = BonusCard::all();
        if ($allCards->isEmpty()) return;
        $bonusCards = $allCards->where('kind', 'bonus')->values();
        $malusCards = $allCards->where('kind', '!=', 'bonus')->values();

        $state = $gameSave->state ?? [];
        $offersByTeam = [];

        foreach ($teams as $team) {
            // Position dans le classement (1 = premier, $total = dernier)
            $position = $standings[$team->id] ?? $total;

            // Plus l'équipe est mal classée, plus elle a de chances d'avoir de l'Or
            $weights = $this->computeTierWeights($position, $total);

            // 3 cartes bonus + 2 cartes malus par semaine.
            $offers = array_merge(
                $this->buildOffers($bonusCards, $weights, 3),
                $this->buildOffers($malusCards, $weights, 2),
            );

            $offersByTeam[(string) $team->id] = $offers;
        }

        $state['shop'] = [
            'season'          => $season,
            'week'            => $week,
            'offers_by_team'  => $offersByTeam,
        ];

        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Retourne les offres de la semaine pour une équipe donnée.
     * Régénère si la boutique est périmée (mauvaise semaine/saison).
     */
    public function getOffersForTeam(GameSave $gameSave, int $teamId): array
    {
        $shop = $gameSave->state['shop'] ?? null;

        if (
            !$shop ||
            (int) ($shop['season'] ?? 0) !== (int) $gameSave->season ||
            (int) ($shop['week']   ?? 0) !== (int) $gameSave->week
        ) {
            $this->generateWeeklyOffers($gameSave);
            $gameSave->refresh();
            $shop = $gameSave->state['shop'] ?? [];
        }

        return $shop['offers_by_team'][(string) $teamId] ?? [];
    }

    // ──────────────────────────────────────────────────────────
    //   HELPERS PRIVÉS
    // ──────────────────────────────────────────────────────────

    /**
     * Tire $count offres distinctes dans un catalogue donné (bonus ou malus),
     * en respectant la pondération de tier et les base_weight.
     *
     * @return array<int, array>
     */
    private function buildOffers(Collection $catalog, array $weights, int $count): array
    {
        if ($catalog->isEmpty()) return [];

        $offers = [];
        $usedCardIds = [];

        for ($i = 0; $i < $count; $i++) {
            $tier = $this->drawTier($weights);
            $card = $this->drawCard($catalog, $tier, $usedCardIds);
            if (!$card) continue;

            $usedCardIds[] = $card->id;
            $offers[] = [
                'bonus_card_id'   => $card->id,
                'kind'            => $card->kind,
                'tier'            => $tier,
                'cost'            => $card->cost,
                'name'            => $card->name,
                'description'     => $card->description,
                'icon'            => $card->icon,
                'target'          => $card->target,
                'execution_phase' => $card->execution_phase,
                'effect_type'     => $card->effect_type,
                'effect_value'    => $card->effect_value,
            ];
        }

        return $offers;
    }

    /**
     * Calcule les poids Bronze/Argent/Or selon la position.
     * Dernier → plus de Gold. Premier → quasi que du Bronze.
     */
    private function computeTierWeights(int $position, int $total): array
    {
        if ($total <= 1) return ['bronze' => 70, 'silver' => 25, 'gold' => 5];

        // ratio : 0 = premier, 1 = dernier
        $ratio = ($position - 1) / ($total - 1);

        return [
            'bronze' => (int) round(70 - $ratio * 50),  // 70% → 20%
            'silver' => (int) round(25 + $ratio * 15),  // 25% → 40%
            'gold'   => (int) round(5  + $ratio * 35),  // 5%  → 40%
        ];
    }

    /**
     * Tire un tier aléatoire selon les poids.
     */
    private function drawTier(array $weights): string
    {
        $total = array_sum($weights);
        $rand  = rand(1, $total);
        $cumul = 0;
        foreach ($weights as $tier => $w) {
            $cumul += $w;
            if ($rand <= $cumul) return $tier;
        }
        return 'bronze';
    }

    /**
     * Tire une carte du catalogue pour un tier donné,
     * en évitant les doublons et en respectant les base_weight.
     */
    private function drawCard(Collection $cards, string $tier, array $excludeIds): ?BonusCard
    {
        $pool = $cards
            ->where('tier', $tier)
            ->whereNotIn('id', $excludeIds)
            ->values();

        if ($pool->isEmpty()) {
            // Fallback : n'importe quel tier si le tier est vide
            $pool = $cards->whereNotIn('id', $excludeIds)->values();
        }
        if ($pool->isEmpty()) return null;

        $totalWeight = $pool->sum('base_weight');
        $rand = rand(1, max(1, $totalWeight));
        $cumul = 0;

        foreach ($pool as $card) {
            $cumul += $card->base_weight;
            if ($rand <= $cumul) return $card;
        }

        return $pool->first();
    }

    /**
     * Construit un classement simplifié [team_id => position].
     */
    private function buildStandings(GameSave $gameSave, Collection $teams): array
    {
        $sorted = $teams->sortByDesc(fn ($t) =>
            ($t->wins ?? 0) * 3 + ($t->draws ?? 0)
        )->values();

        $standings = [];
        foreach ($sorted as $i => $team) {
            $standings[$team->id] = $i + 1;
        }
        return $standings;
    }
}

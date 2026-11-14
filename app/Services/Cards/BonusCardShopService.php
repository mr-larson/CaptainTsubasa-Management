<?php

namespace App\Services\Cards;

use App\Contracts\Randomizer;
use App\Models\BonusCard;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;

/**
 * Boutique de cartes bonus/malus : tire chaque semaine 3 offres par équipe dans le catalogue.
 * Les chances de palier (bronze, argent, or) dépendent du classement : plus l'équipe est mal classée, plus l'or est probable. Les offres sont stockées dans l'état de la partie.
 */
class BonusCardShopService
{

    /** Reçoit le générateur aléatoire (tirages de palier et de carte). */
    public function __construct(private Randomizer $random)
    {
    }
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

        $malusEnabled = $gameSave->getConfig('bonus_cards_enabled', true)
            && $gameSave->getConfig('malus_cards_enabled', true);

        $allCards = BonusCard::all();
        if (!$malusEnabled) {
            $allCards = $allCards->where('kind', '!=', 'malus');
        }
        if ($allCards->isEmpty()) return;

        $offersByTeam = [];

        foreach ($teams as $team) {
            // Position dans le classement (1 = premier, $total = dernier)
            $position = $standings[$team->id] ?? $total;

            // Plus l'équipe est mal classée, plus elle a de chances d'avoir de l'Or
            $weights = $this->computeTierWeights($position, $total);

            // 3 offres par semaine, tirées dans le catalogue commun : chaque
            // offre peut être bonus ou malus (le tirage respecte les base_weight).
            $offers = $this->buildOffers($allCards, $weights, 3);

            $offersByTeam[(string) $team->id] = $offers;
        }

        $gameSave->gameState()->putShop((int) $season, (int) $week, $offersByTeam);
        $gameSave->save();
    }

    /** La boutique n'a pas été tirée pour la semaine / saison courante. */
    public function isStale(GameSave $gameSave): bool
    {
        return !$gameSave->gameState()->isShopFor((int) $gameSave->season, (int) $gameSave->week);
    }

    /**
     * Tire les offres si elles sont périmées. À appeler sous verrou de save
     * (withSaveLock) : le tirage réécrit state.shop avec tout le state.
     */
    public function ensureFresh(GameSave $gameSave): void
    {
        if ($this->isStale($gameSave)) {
            $this->generateWeeklyOffers($gameSave);
        }
    }

    /**
     * Retourne les offres de la semaine pour une équipe donnée.
     * Régénère si la boutique est périmée (mauvaise semaine/saison).
     */
    public function getOffersForTeam(GameSave $gameSave, int $teamId): array
    {
        if ($this->isStale($gameSave)) {
            // Régénération : à appeler sous verrou de save (voir ensureFresh) ;
            // ce repli ne sert qu'aux appelants qui n'ont pas pu le faire.
            $this->generateWeeklyOffers($gameSave);
            $gameSave->refresh();
        }

        $offers = $gameSave->gameState()->shopOffers($teamId);

        $malusEnabled = $gameSave->getConfig('bonus_cards_enabled', true)
            && $gameSave->getConfig('malus_cards_enabled', true);

        if (!$malusEnabled) {
            $offers = array_values(array_filter($offers, fn($o) => ($o['kind'] ?? 'bonus') !== 'malus'));
        }

        return array_map(function (array $offer) {
            $offer['cost'] = BonusCard::SHOP_PRICE;
            if (($offer['effect_type'] ?? null) === 'opponent_stamina_drain') {
                $offer['description'] = BonusCard::teamStaminaDrainDescription($offer['tier']);
                $offer['effect_value']['amount'] = BonusCard::teamStaminaDrainAmount($offer['tier']);
            }
            return $offer;
        }, $offers);
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
                'tier'            => $card->effect_type === 'opponent_stamina_drain' ? $card->tier : $tier,
                'cost'            => BonusCard::SHOP_PRICE,
                'name'            => $card->name,
                'description'     => $card->description,
                'icon'            => $card->icon,
                'target'          => $card->target->value,
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
        $rand  = $this->random->int(1, $total);
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
        $rand = $this->random->int(1, max(1, $totalWeight));
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

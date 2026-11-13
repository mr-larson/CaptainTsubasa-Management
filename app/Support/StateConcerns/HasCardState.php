<?php

namespace App\Support\StateConcerns;

/**
 * Accesseurs typés du `state` de la sauvegarde — Cartes bonus et malus : effets en attente (`active_pre_match_cards`, `pending_stat_debuffs`, `pending_malus`, `malus_shields`, `pending_sponsor_challenges`, `sponsor_results`) et boutique de la semaine (`shop`).
 *
 * Fait partie de GameState (qui l'utilise) : s'appuie sur ses méthodes get(), put()
 * et forget(). La forme stockée dans le JSON est décrite au-dessus de chaque clé.
 */
trait HasCardState
{
    // ── cartes bonus / malus : effets en attente ──────────────────────
    //    Chaque liste est ajoutée à l'activation d'une carte et purgée une fois
    //    l'effet consommé (match joué, fin de semaine).

    /** Boosts « prochain match » : [['game_bonus_card_id', 'game_team_id', 'effect_type', 'effect_value']] */
    public function activePreMatchCards(): array
    {
        return $this->get('active_pre_match_cards', []);
    }

    /** Arme une carte pour le prochain match de son équipe. */
    public function addActivePreMatchCard(array $card): void
    {
        $this->put('active_pre_match_cards', [...$this->activePreMatchCards(), $card]);
    }

    /** Remplace la liste des cartes armées (après consommation d'une partie d'entre elles). */
    public function putActivePreMatchCards(array $cards): void
    {
        $this->put('active_pre_match_cards', array_values($cards));
    }

    /** Debuffs de stats reçus par une équipe cible pour son prochain match. */
    public function pendingStatDebuffs(): array
    {
        return $this->get('pending_stat_debuffs', []);
    }

    /** Enregistre un debuff de stats que l'équipe cible subira à son prochain match. */
    public function addPendingStatDebuff(array $debuff): void
    {
        $this->put('pending_stat_debuffs', [...$this->pendingStatDebuffs(), $debuff]);
    }

    /** Remplace la liste des debuffs en attente (après consommation d'une partie d'entre eux). */
    public function putPendingStatDebuffs(array $debuffs): void
    {
        $this->put('pending_stat_debuffs', array_values($debuffs));
    }

    /** Malus offensifs en attente (titulaire consigné…) : ['target_team_id', 'target_player_id', 'effect_type', ...] */
    public function pendingMalus(): array
    {
        return $this->get('pending_malus', []);
    }

    /** Enregistre un malus offensif en attente contre une équipe (ex. titulaire consigné). */
    public function addPendingMalus(array $malus): void
    {
        $this->put('pending_malus', [...$this->pendingMalus(), $malus]);
    }

    /** Remplace la liste des malus en attente (après consommation d'une partie d'entre eux). */
    public function putPendingMalus(array $malus): void
    {
        $this->put('pending_malus', array_values($malus));
    }

    /** @return array<int, int> ids des équipes protégées par un bouclier (un exemplaire par carte active) */
    public function malusShields(): array
    {
        return array_map('intval', $this->get('malus_shields', []));
    }

    /** Ajoute un bouclier anti-malus à une équipe : le prochain malus qu'elle subira sera annulé. */
    public function addMalusShield(int $teamId): void
    {
        $this->put('malus_shields', [...$this->malusShields(), $teamId]);
    }

    /** Consomme un bouclier de l'équipe ; false si elle n'en a pas. */
    public function consumeMalusShield(int $teamId): bool
    {
        $shields = $this->malusShields();
        $index = array_search($teamId, $shields, true);
        if ($index === false) return false;

        unset($shields[$index]);
        $this->put('malus_shields', array_values($shields));

        return true;
    }

    /** Défis sponsors (cartes finance) en cours : ['game_team_id', 'challenge', 'reward', ...] */
    public function pendingSponsorChallenges(): array
    {
        return $this->get('pending_sponsor_challenges', []);
    }

    /** Enregistre un défi sponsor (carte finance) à résoudre à la clôture de la semaine. */
    public function addSponsorChallenge(array $challenge): void
    {
        $this->put('pending_sponsor_challenges', [...$this->pendingSponsorChallenges(), $challenge]);
    }

    /** Remplace la liste des défis sponsors en cours (après résolution d'une partie d'entre eux). */
    public function putPendingSponsorChallenges(array $challenges): void
    {
        $this->put('pending_sponsor_challenges', array_values($challenges));
    }

    /** Récap de la dernière clôture des défis sponsors. */
    public function sponsorResults(): array
    {
        return $this->get('sponsor_results', []);
    }

    /** Remplace le récap des défis sponsors de la dernière clôture. */
    public function putSponsorResults(array $results): void
    {
        $this->put('sponsor_results', $results);
    }

    // ── shop : offres de la boutique de la semaine ────────────────────
    //    ['season' => int, 'week' => int, 'offers_by_team' => [teamId => [offre]]]

    /** La boutique de cartes tirée pour la semaine, ou null si elle n'a pas été tirée. */
    public function shop(): ?array
    {
        return $this->get('shop');
    }

    /** Les offres ont-elles été tirées pour CETTE saison et semaine ? */
    public function isShopFor(int $season, int $week): bool
    {
        $shop = $this->shop();

        return $shop !== null
            && (int) ($shop['season'] ?? 0) === $season
            && (int) ($shop['week'] ?? 0) === $week;
    }

    /** Enregistre la boutique tirée pour la saison et la semaine données, avec les offres de chaque équipe. */
    public function putShop(int $season, int $week, array $offersByTeam): void
    {
        $this->put('shop', ['season' => $season, 'week' => $week, 'offers_by_team' => $offersByTeam]);
    }

    /** Offres de la boutique pour une équipe (liste vide si aucune). */
    public function shopOffers(int $teamId): array
    {
        return $this->shop()['offers_by_team'][(string) $teamId] ?? [];
    }
}

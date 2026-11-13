<?php

namespace App\Support\StateConcerns;

/**
 * Accesseurs typés du `state` de la sauvegarde — Événements du cycle d'une saison : pause de mi-saison, bilan de fin de saison et historique, agents libres signés, boosts de déclarations pour le prochain match, dernier passage des départs de joueurs.
 *
 * Fait partie de GameState (qui l'utilise) : s'appuie sur ses méthodes get(), put()
 * et forget(). La forme stockée dans le JSON est décrite au-dessus de chaque clé.
 */
trait HasSeasonCycleState
{
    // ── midseason_break : pause de mi-saison ──────────────────────────
    //    ['return_week' => int, 'completed' => bool]

    /** La pause de mi-saison enregistrée (semaine de retour, terminée ou non), vide si aucune. */
    public function midseasonBreak(): array
    {
        return $this->get('midseason_break', []);
    }

    /** Programme la pause de mi-saison (sans effet si elle est déjà programmée). */
    public function scheduleMidseasonBreak(int $returnWeek): void
    {
        if ($this->get('midseason_break') === null) {
            $this->put('midseason_break', ['return_week' => $returnWeek, 'completed' => false]);
        }
    }

    /** Marque la pause de mi-saison comme terminée. */
    public function completeMidseasonBreak(): void
    {
        $break = $this->midseasonBreak();
        $break['completed'] = true;
        $this->put('midseason_break', $break);
    }

    /** La pause est-elle programmée pour la semaine donnée et pas encore terminée ? */
    public function hasPendingMidseasonBreak(int $currentWeek): bool
    {
        $break = $this->midseasonBreak();

        return (int) ($break['return_week'] ?? 0) === $currentWeek && ! ($break['completed'] ?? true);
    }

    // ── season_history / last_season_recap ────────────────────────────

    /** Enregistre le bilan de fin de saison : dans l'historique et comme dernier bilan affiché. */
    public function recordSeasonRecap(int $season, array $recap): void
    {
        $history = $this->get('season_history', []);
        $history[$season] = $recap;
        $this->put('season_history', $history);
        $this->put('last_season_recap', $recap);
    }

    /** Le bilan de la dernière saison terminée, ou null. */
    public function lastSeasonRecap(): ?array
    {
        return $this->get('last_season_recap');
    }

    // ── free_agent_signings : agents libres signés cette saison ───────

    /** Ajoute une signature d'agent libre à la liste de la saison. */
    public function recordFreeAgentSigning(array $signing): void
    {
        $signings = $this->get('free_agent_signings', []);
        $signings[] = $signing;
        $this->put('free_agent_signings', $signings);
    }

    /** Efface les signatures d'agents libres (nouvelle saison). */
    public function forgetFreeAgentSignings(): void
    {
        $this->forget('free_agent_signings');
    }

    // ── declaration_match_boosts : [matchId][teamId][playerId] => modificateur

    /** Boosts de déclaration d'une équipe pour un match : [joueur → modificateur]. */
    public function declarationBoosts(int $matchId, int $teamId): array
    {
        return $this->get('declaration_match_boosts', [])[$matchId][$teamId] ?? [];
    }

    /** Ajoute un boost de déclaration pour un joueur sur un match donné. */
    public function addDeclarationBoost(int $matchId, int $teamId, int $playerId, mixed $modifier): void
    {
        $boosts = $this->get('declaration_match_boosts', []);
        $boosts[$matchId][$teamId][$playerId] = $modifier;
        $this->put('declaration_match_boosts', $boosts);
    }

    /** Retire les boosts d'un match ; false s'il n'y en avait pas. */
    public function consumeDeclarationBoosts(int $matchId): bool
    {
        $boosts = $this->get('declaration_match_boosts', []);
        if (! isset($boosts[$matchId])) return false;

        unset($boosts[$matchId]);
        $this->put('declaration_match_boosts', $boosts);

        return true;
    }

    /** Efface tous les boosts de déclaration (nouvelle saison). */
    public function forgetDeclarationBoosts(): void
    {
        $this->forget('declaration_match_boosts');
    }

    // ── contract_departures : dernier passage des départs de joueurs ──

    /** @return array{season?: int, week?: int, entries?: array} */
    public function contractDepartures(): array
    {
        return $this->get('contract_departures', []);
    }

    /** Les départs de fin de contrat ont-ils déjà été traités pour cette semaine ? */
    public function contractDeparturesDoneFor(int $season, int $week): bool
    {
        $last = $this->contractDepartures();

        return ($last['season'] ?? null) === $season && ($last['week'] ?? null) === $week;
    }

    /** Enregistre le passage des départs de joueurs pour la semaine, avec la liste des départs. */
    public function putContractDepartures(int $season, int $week, array $entries): void
    {
        $this->put('contract_departures', ['season' => $season, 'week' => $week, 'entries' => $entries]);
    }
}

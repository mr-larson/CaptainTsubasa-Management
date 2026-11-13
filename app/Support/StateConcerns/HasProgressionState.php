<?php

namespace App\Support\StateConcerns;

/**
 * Accesseurs typés du `state` de la sauvegarde — Progression des joueurs après les matchs : gains de stats cumulés sur la saison (`season_progression`) et derniers matchs traités (`match_progression_history`).
 *
 * Fait partie de GameState (qui l'utilise) : s'appuie sur ses méthodes get(), put()
 * et forget(). La forme stockée dans le JSON est décrite au-dessus de chaque clé.
 */
trait HasProgressionState
{
    // ── season_progression : gains de stats par joueur sur la saison ──
    //    [season => [playerId => total gagné]] (plafonné par joueur et par saison)

    /** Ne garde que la saison en cours : les précédentes ne servent plus au plafond. */
    public function keepOnlySeasonProgression(int $season): void
    {
        $this->put('season_progression', [$season => $this->get('season_progression', [])[$season] ?? []]);
    }

    /** Total de points de stats gagnés par un joueur sur la saison (0 s'il n'en a pas gagné). */
    public function playerSeasonProgression(int $season, int $playerId): int
    {
        return (int) ($this->get('season_progression', [])[$season][$playerId] ?? 0);
    }

    /** Enregistre le total de points de stats gagnés par un joueur sur la saison. */
    public function setPlayerSeasonProgression(int $season, int $playerId, int $total): void
    {
        $progression = $this->get('season_progression', []);
        $progression[$season][$playerId] = $total;
        $this->put('season_progression', $progression);
    }

    // ── match_progression_history : derniers matchs traités (50) ──────

    /** @param array{match_id: int, week: int, season: int, count: int} $entry */
    public function recordMatchProgression(array $entry): void
    {
        $history = $this->get('match_progression_history', []);
        $history[] = $entry;
        $this->put('match_progression_history', array_slice($history, -50));
    }
}

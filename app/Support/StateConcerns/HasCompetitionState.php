<?php

namespace App\Support\StateConcerns;

/**
 * Accesseurs typés du `state` de la sauvegarde — Compétition : tournoi de la Coupe du Monde (`tournament`) et mandat de la direction en mode Ligue (`career`).
 *
 * Fait partie de GameState (qui l'utilise) : s'appuie sur ses méthodes get(), put()
 * et forget(). La forme stockée dans le JSON est décrite au-dessus de chaque clé.
 */
trait HasCompetitionState
{
    // ── tournament : Coupe du Monde (poules puis élimination) ─────────
    //    ['groups' => [...], 'stage' => 'group'|...|'done', 'group_last_week' => int, 'champion_team_id' => ?int, ...]
    //    (détail de la structure : TournamentService)

    /** La structure du tournoi de Coupe du Monde (poules, étape, champion), ou null. */
    public function tournament(): ?array
    {
        return $this->get('tournament');
    }

    /** Remplace la structure du tournoi. */
    public function putTournament(array $tournament): void
    {
        $this->put('tournament', $tournament);
    }

    /** Étape courante du tournoi (poule, élimination… ou « done » une fois terminé), ou null. */
    public function tournamentStage(): ?string
    {
        return $this->tournament()['stage'] ?? null;
    }

    // ── career : mandat de la direction, jauge de confiance ───────────
    //    (détail de la structure : CareerObjectiveService)

    /** Les données de carrière du mode Ligue (mandat, confiance, historique), vides si absentes. */
    public function career(): array
    {
        return $this->get('career', []);
    }

    /** Remplace les données de carrière. */
    public function putCareer(array $career): void
    {
        $this->put('career', $career);
    }
}

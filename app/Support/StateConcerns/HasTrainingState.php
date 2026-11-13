<?php

namespace App\Support\StateConcerns;

/**
 * Accesseurs typés du `state` de la sauvegarde — Entraînements : séances de la semaine (`training`), historique des séances humaines (`training_history`) et des équipes IA (`ai_training_history`, `ai_training_week`).
 *
 * Fait partie de GameState (qui l'utilise) : s'appuie sur ses méthodes get(), put()
 * et forget(). La forme stockée dans le JSON est décrite au-dessus de chaque clé.
 */
trait HasTrainingState
{
    // ── training : séances de la semaine ──────────────────────────────
    //    ['season' => int, 'week' => int, 'entries' => [séance], 'ai_entries' => [séance]]
    //    séance = ['team_id', 'player_id', 'stat', 'gain', 'stamina_cost', 'cost', 'created_at', ...]

    /** Les séances d'entraînement enregistrées pour la semaine courante, ou null. */
    public function training(): ?array
    {
        return $this->get('training');
    }

    /** Séances enregistrées pour CETTE saison et semaine (toutes équipes), sinon []. */
    public function weekTrainingEntries(int $season, int $week): array
    {
        $training = $this->training();

        return $training
            && (int) ($training['season'] ?? 0) === $season
            && (int) ($training['week'] ?? 0) === $week
            ? ($training['entries'] ?? [])
            : [];
    }

    /** Remplace les séances de la semaine (les séances IA de l'état précédent sont abandonnées). */
    public function setWeekTraining(int $season, int $week, array $entries): void
    {
        $this->put('training', ['season' => $season, 'week' => $week, 'entries' => $entries]);
    }

    /** Enregistre les séances IA de la semaine en gardant les séances humaines de cette semaine. */
    public function setAiTraining(int $season, int $week, array $aiEntries): void
    {
        $this->put('training', [
            'season'     => $season,
            'week'       => $week,
            'entries'    => $this->weekTrainingEntries($season, $week),
            'ai_entries' => $aiEntries,
        ]);
    }

    // ── training_history : séances humaines de la saison (200 dernières) ─

    /** null tant qu'aucun historique n'a été constitué (anciennes sauvegardes). */
    public function trainingHistory(): ?array
    {
        return $this->get('training_history');
    }

    /** Remplace l'historique des séances humaines. */
    public function putTrainingHistory(array $history): void
    {
        $this->put('training_history', $history);
    }

    // ── ai_training_history / ai_training_week : séances des équipes IA ─

    /** Historique des séances d'entraînement des équipes IA (saison en cours). */
    public function aiTrainingHistory(): array
    {
        return $this->get('ai_training_history', []);
    }

    /** Séances IA d'une saison donnée. */
    public function aiTrainingHistoryOfSeason(int $season): array
    {
        return array_values(array_filter(
            $this->aiTrainingHistory(),
            fn ($entry) => (int) ($entry['season'] ?? 0) === $season,
        ));
    }

    /** Séances IA d'une semaine donnée de la saison. */
    public function aiTrainingHistoryOfWeek(int $season, int $week): array
    {
        return array_values(array_filter(
            $this->aiTrainingHistory(),
            fn ($entry) => (int) ($entry['season'] ?? 0) === $season && (int) ($entry['week'] ?? 0) === $week,
        ));
    }

    /** Remplace l'historique des séances IA. */
    public function putAiTrainingHistory(array $history): void
    {
        $this->put('ai_training_history', $history);
    }

    /** Dernière semaine pour laquelle l'entraînement IA a été traité (garde anti-doublon). */
    public function aiTrainingWeek(): mixed
    {
        return $this->get('ai_training_week');
    }

    /** Marque la semaine comme traitée pour l'entraînement IA (évite un double passage). */
    public function markAiTrainingWeek(mixed $week): void
    {
        $this->put('ai_training_week', $week);
    }
}

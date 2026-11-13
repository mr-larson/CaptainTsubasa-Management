<?php

namespace App\Support\StateConcerns;

/**
 * Accesseurs typés du `state` de la sauvegarde — État de la draft en cours (`draft`) : ordre des équipes, enveloppes, tour, équipes ayant fini.
 *
 * Fait partie de GameState (qui l'utilise) : s'appuie sur ses méthodes get(), put()
 * et forget(). La forme stockée dans le JSON est décrite au-dessus de chaque clé.
 */
trait HasDraftState
{
    // ── draft : état de la draft en cours ─────────────────────────────
    //    ['order' => [teamId], 'budgets' => [teamId => int], 'pricing' => 'progressive',
    //     'current_pick_index' => int, 'round' => int, 'picks' => [...], 'completed' => bool,
    //     'finished_teams' => [teamId], 'exhausted_teams' => [teamId]]
    //    Absente hors draft (purgée à l'ouverture de Play, voir DraftService::purgeFinishedDraft).

    /** null s'il n'y a pas de draft. */
    public function draft(): ?array
    {
        return $this->get('draft');
    }

    /** Y a-t-il une draft enregistrée (en cours, ou terminée mais pas encore purgée) ? */
    public function hasDraft(): bool
    {
        return $this->draft() !== null;
    }

    /** Remplace l'état de la draft. */
    public function putDraft(array $draft): void
    {
        $this->put('draft', $draft);
    }

    /** Supprime l'état de la draft une fois terminée. */
    public function forgetDraft(): void
    {
        $this->forget('draft');
    }

    /** Ouvre une draft : ordre des équipes (premier tour) et enveloppe par équipe. */
    public function startDraft(array $order, array $budgets): void
    {
        $this->putDraft([
            'order'              => array_values($order),
            'budgets'            => $budgets,
            'pricing'            => 'progressive',
            'current_pick_index' => 0,
            'round'              => 1,
            'picks'              => [],
            'completed'          => false,
            'finished_teams'     => [],
        ]);
    }

    /** La draft est-elle terminée ? */
    public function draftCompleted(): bool
    {
        return (bool) ($this->draft()['completed'] ?? false);
    }

    /** Numéro du tour de draft courant (1 par défaut). */
    public function draftRound(): int
    {
        return (int) ($this->draft()['round'] ?? 1);
    }

    /** Enveloppes par équipe ; null pour une ancienne draft à caisse commune. */
    public function draftBudgets(): ?array
    {
        return $this->draft()['budgets'] ?? null;
    }

    /** Mode de tarification des picks (« progressive »), ou null pour une ancienne draft. */
    public function draftPricing(): ?string
    {
        return $this->draft()['pricing'] ?? null;
    }

    /** Équipe qui doit choisir (ordre « serpent » : les tours pairs sont inversés) ; null si terminé. */
    public function currentDraftTeamId(): ?int
    {
        $draft = $this->draft();
        if (! $draft || ($draft['completed'] ?? false)) return null;

        $order = $draft['order'] ?? [];
        $effectiveOrder = ((int) ($draft['round'] ?? 1) % 2 === 0) ? array_reverse($order) : $order;
        $teamId = $effectiveOrder[$draft['current_pick_index'] ?? 0] ?? null;

        return $teamId === null ? null : (int) $teamId;
    }
}

<?php

namespace App\Support\StateConcerns;

/**
 * Accesseurs typés du `state` de la sauvegarde — Compositions enregistrées par équipe (`lineup`) et repos tournant des équipes IA (`ai_rotation`).
 *
 * Fait partie de GameState (qui l'utilise) : s'appuie sur ses méthodes get(), put()
 * et forget(). La forme stockée dans le JSON est décrite au-dessus de chaque clé.
 */
trait HasLineupState
{
    // ── lineup : compositions enregistrées, par équipe ────────────────
    //    [teamId => ['formation' => string, 'slots' => [slot 1..11 => gamePlayerId|null]]]

    /** @return array<int, array{formation?: string, slots?: array<int, int|null>}> */
    public function lineups(): array
    {
        return $this->get('lineup', []);
    }

    /** Slots enregistrés d'une équipe, ou null si aucune composition n'est enregistrée. */
    public function lineupSlots(int $teamId): ?array
    {
        return $this->lineups()[$teamId]['slots'] ?? null;
    }

    /** Enregistre (en fusionnant) la composition d'une équipe : formation et/ou slots. */
    public function setLineup(int $teamId, array $lineup): void
    {
        $lineups = $this->lineups();
        $lineups[$teamId] = array_replace($lineups[$teamId] ?? [], $lineup);
        $this->put('lineup', $lineups);
    }

    /** Enregistre les slots (poste 1 à 11 → joueur) d'une équipe, sans toucher à sa formation. */
    public function setLineupSlots(int $teamId, array $slots): void
    {
        $this->setLineup($teamId, ['slots' => $slots]);
    }

    /** Retire un joueur des slots d'une équipe (sans toucher aux autres). */
    public function removeFromLineup(int $teamId, int $playerId): void
    {
        $slots = $this->lineupSlots($teamId);
        if ($slots === null) return;

        $changed = false;
        foreach ($slots as $slot => $id) {
            if ((int) $id === $playerId) {
                $slots[$slot] = null;
                $changed = true;
            }
        }
        if ($changed) $this->setLineupSlots($teamId, $slots);
    }

    /** Remplace un joueur par un autre dans les slots d'une équipe. */
    public function replaceInLineup(int $teamId, int $outId, int $inId): void
    {
        $slots = $this->lineupSlots($teamId);
        if ($slots === null) return;

        $changed = false;
        foreach ($slots as $slot => $id) {
            if ((int) $id === $outId) {
                $slots[$slot] = $inId;
                $changed = true;
            }
        }
        if ($changed) $this->setLineupSlots($teamId, $slots);
    }

    /** Efface toutes les compositions enregistrées (nouvelle saison). */
    public function forgetLineups(): void
    {
        $this->forget('lineup');
    }

    // ── ai_rotation : repos tournant décidé pour une équipe IA ────────
    //    [teamId => ['season' => int, 'week' => int, 'resting_roles' => [playerId => role]]]

    /** Le dernier repos tournant décidé pour une équipe IA (saison, semaine, rôles au repos). */
    public function aiRotation(int $teamId): array
    {
        return $this->get('ai_rotation', [])[$teamId] ?? [];
    }

    /** Enregistre le repos tournant d'une équipe IA pour une semaine donnée. */
    public function setAiRotation(int $teamId, int $season, int $week, array $restingRoles): void
    {
        $rotations = $this->get('ai_rotation', []);
        $rotations[$teamId] = ['season' => $season, 'week' => $week, 'resting_roles' => $restingRoles];
        $this->put('ai_rotation', $rotations);
    }

    /** Rôles au repos s'ils ont été décidés pour CETTE semaine, sinon []. */
    public function restingRoles(int $teamId, int $season, int $week): array
    {
        $rotation = $this->aiRotation($teamId);

        return ($rotation['week'] ?? null) === $week && ($rotation['season'] ?? null) === $season
            ? ($rotation['resting_roles'] ?? [])
            : [];
    }
}

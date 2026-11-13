<?php

namespace App\Support\StateConcerns;

/**
 * Accesseurs typés du `state` de la sauvegarde — Défis de saison (`challenges`) : tirage de la saison et entrée de chaque équipe.
 *
 * Fait partie de GameState (qui l'utilise) : s'appuie sur ses méthodes get(), put()
 * et forget(). La forme stockée dans le JSON est décrite au-dessus de chaque clé.
 */
trait HasChallengesState
{
    // ── challenges : défis de saison ──────────────────────────────────
    //    ['season' => int, 'drawn_at' => iso8601, 'teams' => [teamId => entrée]]
    //    entrée = ['baseline_budget', 'expected_rank', 'objectives' => [...], 'paid_rewards' => [...]]
    //    (détail de l'entrée : SeasonChallengeService)

    /** Les défis de saison, ou null s'ils n'ont pas encore été tirés. */
    public function challenges(): ?array
    {
        return $this->get('challenges');
    }

    /** Saison pour laquelle les défis ont été tirés ; 0 s'ils ne l'ont pas encore été. */
    public function challengesSeason(): int
    {
        return (int) ($this->challenges()['season'] ?? 0);
    }

    /** @return array<int|string, array> entrées par équipe */
    public function challengeTeams(): array
    {
        return $this->challenges()['teams'] ?? [];
    }

    /** L'entrée de défis d'une équipe (objectifs, rang attendu, primes déjà versées), ou null. */
    public function challengeEntry(int $teamId): ?array
    {
        $teams = $this->challengeTeams();

        return $teams[$teamId] ?? $teams[(string) $teamId] ?? null;
    }

    /** Enregistre le tirage des défis de la saison, avec l'entrée de chaque équipe. */
    public function startChallenges(int $season, string $drawnAt, array $teams): void
    {
        $this->put('challenges', ['season' => $season, 'drawn_at' => $drawnAt, 'teams' => $teams]);
    }

    /** Remplace tous les défis (après versement de primes qui modifie les entrées). */
    public function putChallenges(array $challenges): void
    {
        $this->put('challenges', $challenges);
    }
}

<?php

namespace App\Services\Concerns;

/**
 * Logique partagée d'évaluation des joueurs (poste, overall) utilisée par
 * tous les services de sélection/recrutement IA (draft, rotation hebdo,
 * transferts, remplacement lié aux cartes malus).
 */
trait EvaluatesPlayers
{
    /**
     * Poids des stats par groupe de poste pour le calcul de l'overall.
     * `stamina` est volontairement exclue : c'est un état transitoire
     * (fatigue), pas une mesure de qualité du joueur — la fatigue est gérée
     * séparément via des seuils dédiés.
     */
    private const OVERALL_WEIGHTS = [
        'GK'  => ['hand_save' => 0.35, 'punch_save' => 0.25, 'block' => 0.15, 'intercept' => 0.10, 'defense' => 0.10, 'speed' => 0.05],
        'DEF' => ['tackle' => 0.25, 'block' => 0.20, 'intercept' => 0.20, 'defense' => 0.15, 'heading' => 0.10, 'speed' => 0.10],
        'MID' => ['pass' => 0.30, 'dribble' => 0.20, 'attack' => 0.20, 'defense' => 0.15, 'speed' => 0.15],
        'ATT' => ['shot' => 0.30, 'attack' => 0.25, 'dribble' => 0.20, 'speed' => 0.15, 'heading' => 0.10],
    ];

    protected function positionGroup(string $position): string
    {
        $p = strtoupper(trim($position));
        if (str_contains($p, 'GK') || str_contains($p, 'GOAL'))    return 'GK';
        if (str_contains($p, 'DEF') || str_contains($p, 'BACK'))   return 'DEF';
        if (str_contains($p, 'MDF') || str_contains($p, 'MID') || str_contains($p, 'MOF')) return 'MID';
        if (str_contains($p, 'ATT') || str_contains($p, 'FOR'))    return 'ATT';
        return 'MID';
    }

    /**
     * Groupes de poste maîtrisés par un joueur : poste principal + postes secondaires.
     */
    protected function playerPositionGroups($player): array
    {
        if (!$player) return [];

        $groups = [$this->positionGroup($player->position ?? '')];

        foreach ((array) ($player->secondary_positions ?? []) as $secondary) {
            if (is_string($secondary) && $secondary !== '') {
                $groups[] = $this->positionGroup($secondary);
            }
        }

        return array_values(array_unique($groups));
    }

    /**
     * Overall pondéré selon le poste évalué (les stats pertinentes pour ce
     * poste comptent plus). Sans poste précisé, utilise le poste principal
     * du joueur.
     */
    protected function playerOverall($player, ?string $positionGroup = null): int
    {
        if (!$player) return 0;

        $group   = $positionGroup ?? $this->positionGroup($player->position ?? '');
        $weights = self::OVERALL_WEIGHTS[$group] ?? self::OVERALL_WEIGHTS['MID'];

        $score = 0.0;
        foreach ($weights as $stat => $weight) {
            $score += ($player->{$stat} ?? 0) * $weight;
        }

        return (int) round($score);
    }
}

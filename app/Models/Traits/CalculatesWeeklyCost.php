<?php

namespace App\Models\Traits;

/** Calcul du coût hebdomadaire (salaire) d'un joueur à partir de ses stats. */
trait CalculatesWeeklyCost
{
    /**
     * Coût hebdomadaire d'un joueur : moyenne de ses stats utiles au poste (celles du
     * gardien pour un gardien), au moins 10, majorée de 37,5 %. Les stats à 0 sont
     * ignorées ; sans aucune stat, la moyenne vaut 20.
     */
    private function calculateWeeklyCost(array $baseStats, ?string $position = null): int
    {
        if ($position === 'Goalkeeper') {
            $keys = ['speed', 'stamina', 'defense', 'block', 'intercept', 'tackle', 'hand_save', 'punch_save'];
        } else {
            $keys = ['speed', 'stamina', 'attack', 'defense', 'shot', 'pass', 'dribble', 'block', 'intercept', 'tackle'];
        }

        $values = array_map(fn($k) => (int) ($baseStats[$k] ?? 0), $keys);
        $values = array_filter($values, fn($v) => $v > 0);
        $overall = empty($values) ? 20 : array_sum($values) / count($values);

        return max(10, (int) round($overall * 1.375));
    }
}

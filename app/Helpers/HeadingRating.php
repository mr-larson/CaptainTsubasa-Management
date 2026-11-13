<?php

namespace App\Helpers;

use App\Enums\PlayerPosition;

/**
 * Note de jeu de tête d'un joueur, estimée à partir de son poste et de ses statistiques de défense, d'attaque et de blocage.
 */
class HeadingRating
{
    public const DEFAULT_FIELD_RATING = 18;

    /**
     * Note de jeu de tête finale : l'estimation est ramenée dans une fourchette propre au rôle (défenseur, attaquant, milieu ; gardien inchangé).
     */
    public static function forProfile(PlayerPosition|string|null $position, array $stats): int
    {
        $role = FormationHelper::roleFromPosition($position instanceof PlayerPosition ? $position->value : $position);
        $rating = self::estimate($position, $stats);
        [$min, $max, $lowProfile, $highProfile] = match ($role) {
            'DF' => [23, 30, 18, 38],
            'FW' => [18, 26, 16, 40],
            'GK' => [$rating, $rating, 0, 100],
            default => [15, 20, 15, 35],
        };

        $profile = max(0, min(1, ($rating - $lowProfile) / ($highProfile - $lowProfile)));

        return (int) round($min + ($max - $min) * $profile);
    }

    // Calcul historique, conservé pour reconnaître les notes automatiques existantes.
    public static function estimate(PlayerPosition|string|null $position, array $stats): int
    {
        $role = FormationHelper::roleFromPosition($position instanceof PlayerPosition ? $position->value : $position);
        $defense = (int) ($stats['defense'] ?? 50);
        $attack = (int) ($stats['attack'] ?? 50);
        $block = (int) ($stats['block'] ?? $defense);

        $rating = match ($role) {
            'GK' => 0.45 * $defense,
            'DF' => 0.65 * $defense + 0.35 * $block + 3,
            'FW' => 0.55 * $attack + 0.30 * $defense + 0.15 * $block + 2,
            default => 0.60 * $defense + 0.25 * $block + 0.15 * $attack,
        };

        return max(0, min(100, (int) round($rating)));
    }
}

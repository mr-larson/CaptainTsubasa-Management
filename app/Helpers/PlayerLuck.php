<?php

namespace App\Helpers;

/**
 * Constantes et tirages de la chance d'un joueur (valeur entre MIN et MAX, 50 = neutre).
 *
 * La chance influe sur les incidents de match (cartons, blessures) via un multiplicateur.
 */
class PlayerLuck
{
    public const MIN = 20;
    public const MAX = 80;
    public const NEUTRAL = 50;
    public const CRITICAL_RED = 0.10;
    public const CRITICAL_YELLOW = 0.65;
    public const TIE_YELLOW = 0.20;

    /** Tire une chance : moyenne de deux tirages uniformes, donc centrée sur la valeur neutre. */
    public static function roll(): int
    {
        return (int) round((random_int(self::MIN, self::MAX) + random_int(self::MIN, self::MAX)) / 2);
    }

    /**
     * Multiplicateur de probabilité d'incident : moins de risque si la chance est haute, plus si elle est basse (1 à chance neutre).
     */
    public static function incidentMultiplier(?int $luck): float
    {
        $value = max(self::MIN, min(self::MAX, $luck ?? self::NEUTRAL));

        return 1 - ($value - self::NEUTRAL) / 150;
    }
}

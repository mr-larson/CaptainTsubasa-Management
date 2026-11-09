<?php

namespace App\Helpers;

class PlayerLuck
{
    public const MIN = 20;
    public const MAX = 80;
    public const NEUTRAL = 50;
    public const CRITICAL_RED = 0.10;
    public const CRITICAL_YELLOW = 0.65;
    public const TIE_YELLOW = 0.20;

    public static function roll(): int
    {
        return (int) round((random_int(self::MIN, self::MAX) + random_int(self::MIN, self::MAX)) / 2);
    }

    public static function incidentMultiplier(?int $luck): float
    {
        $value = max(self::MIN, min(self::MAX, $luck ?? self::NEUTRAL));

        return 1 - ($value - self::NEUTRAL) / 150;
    }
}

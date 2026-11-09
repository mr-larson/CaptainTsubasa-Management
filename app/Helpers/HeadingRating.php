<?php

namespace App\Helpers;

use App\Enums\PlayerPosition;

class HeadingRating
{
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

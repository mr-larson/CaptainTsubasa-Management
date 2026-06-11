<?php

namespace App\Helpers;

/**
 * Miroir PHP de resources/js/Pages/Match/engine/formations.js.
 * Zones : 0=GK, 1=DEF, 2=MDF, 3=MOF, 4=ATT.
 */
class FormationHelper
{
    public const DEFAULT_FORMATION = '3-2-3-2';

    /** slot (1..11) => zone (0..4) par formation. */
    public const SLOT_ZONES = [
        '3-2-3-2' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 2, 6 => 2, 7 => 3, 8 => 3, 9 => 3, 10 => 4, 11 => 4],
        '3-3-2-2' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 2, 6 => 2, 7 => 2, 8 => 3, 9 => 3, 10 => 4, 11 => 4],
        '3-2-2-3' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 2, 6 => 2, 7 => 3, 8 => 3, 9 => 4, 10 => 4, 11 => 4],
        '3-4-2-1' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 2, 6 => 2, 7 => 2, 8 => 2, 9 => 3, 10 => 3, 11 => 4],
        '3-1-3-3' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 2, 6 => 3, 7 => 3, 8 => 3, 9 => 4, 10 => 4, 11 => 4],
        '4-2-2-2' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 2, 7 => 2, 8 => 3, 9 => 3, 10 => 4, 11 => 4],
        '4-3-2-1' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 2, 7 => 2, 8 => 2, 9 => 3, 10 => 3, 11 => 4],
        '4-1-3-2' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 2, 7 => 3, 8 => 3, 9 => 3, 10 => 4, 11 => 4],
        '4-3-1-2' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 2, 7 => 2, 8 => 2, 9 => 3, 10 => 4, 11 => 4],
        '4-2-1-3' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 2, 7 => 2, 8 => 3, 9 => 4, 10 => 4, 11 => 4],
        '5-2-2-1' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 1, 7 => 2, 8 => 2, 9 => 3, 10 => 3, 11 => 4],
        '5-3-1-1' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 1, 7 => 2, 8 => 2, 9 => 2, 10 => 3, 11 => 4],
        '5-2-1-2' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 1, 7 => 2, 8 => 2, 9 => 3, 10 => 4, 11 => 4],
        '5-1-2-2' => [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 1, 7 => 2, 8 => 3, 9 => 3, 10 => 4, 11 => 4],
    ];

    /** Rôle (GK/DF/MF/FW) d'un slot pour une formation donnée. */
    public static function slotRole(?string $formation, int $slot): ?string
    {
        $zones = self::SLOT_ZONES[$formation] ?? self::SLOT_ZONES[self::DEFAULT_FORMATION];
        $zone  = $zones[$slot] ?? null;

        return match ($zone) {
            0       => 'GK',
            1       => 'DF',
            2, 3    => 'MF',
            4       => 'FW',
            default => null,
        };
    }

    /** Rôle (GK/DF/MF/FW) dérivé d'un libellé de poste ("Goalkeeper", "Forward"…). */
    public static function roleFromPosition(?string $position): ?string
    {
        $p = strtolower(trim((string) $position));
        if ($p === '') return null;
        if (str_contains($p, 'goal') || $p === 'gk') return 'GK';
        if (str_contains($p, 'def') || $p === 'df')  return 'DF';
        if (str_contains($p, 'mid') || $p === 'mf')  return 'MF';
        if (str_contains($p, 'for') || str_contains($p, 'att') || $p === 'fw') return 'FW';
        return null;
    }
}

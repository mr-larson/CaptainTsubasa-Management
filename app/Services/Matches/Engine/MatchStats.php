<?php

namespace App\Services\Matches\Engine;

/**
 * Petites briques de données partagées par les classes du simulateur de match :
 * le bilan vide d'un joueur et la référence d'un joueur embarquée dans le déroulé.
 */
final class MatchStats
{
    /**
     * Bilan vide d'un joueur : tentatives et réussites à 0 pour chaque action offensive et
     * défensive, 0 but, 0 duel gagné ou perdu. Même forme que le bilan lu par PlayerStatsService.
     */
    public static function emptyPlayerStats(): array
    {
        return [
            'offense' => [
                'pass'      => ['attempts' => 0, 'success' => 0],
                'shot'      => ['attempts' => 0, 'success' => 0],
                'dribble'   => ['attempts' => 0, 'success' => 0],
                'special'   => ['attempts' => 0, 'success' => 0],
                'cross'     => ['attempts' => 0, 'success' => 0],
                'long_pass' => ['attempts' => 0, 'success' => 0],
                'goals'     => 0,
            ],
            'defense' => [
                'intercept' => ['attempts' => 0, 'success' => 0],
                'tackle'    => ['attempts' => 0, 'success' => 0],
                'block'     => ['attempts' => 0, 'success' => 0],
                'heading'   => ['attempts' => 0, 'success' => 0],
                'hands'     => ['attempts' => 0, 'success' => 0],
                'punch'     => ['attempts' => 0, 'success' => 0],
                'gkSpecial' => ['attempts' => 0, 'success' => 0],
            ],
            'duelsWon'  => 0,
            'duelsLost' => 0,
        ];
    }

    /** Référence joueur embarquée dans les events du déroulé (replay). */
    public static function eventPlayerRef($player): ?array
    {
        if (!$player || !($player->id ?? null)) return null;

        return [
            'id'     => (int) $player->id,
            'name'   => trim(($player->firstname ?? '') . ' ' . ($player->lastname ?? '')),
            'number' => $player->number ?? null,
        ];
    }
}

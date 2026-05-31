<?php

namespace App\Services;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;

class PlayerStatsService
{
    /**
     * Agrège les stats de tous les matchs joués d'une save
     * depuis game_matches.match_stats (source de vérité DB).
     *
     * Retourne un tableau indexé par game_player_id :
     * [
     *   42 => [
     *     'offense' => ['shot' => ['attempts' => 5, 'success' => 2], ...],
     *     'defense' => ['intercept' => [...], ...],
     *     'duelsWon'  => 8,
     *     'duelsLost' => 3,
     *   ],
     *   ...
     * ]
     */
    public static function aggregateForSave(GameSave $gameSave): array
    {
        $matches = GameMatch::where('game_save_id', $gameSave->id)
            ->where('status', 'played')
            ->whereNotNull('match_stats')
            ->get(['match_stats']);

        $stats = [];

        foreach ($matches as $match) {
            $players = $match->match_stats['players'] ?? [];

            foreach ($players as $pid => $playerStats) {
                $pid = (string) $pid;

                if (!isset($stats[$pid])) {
                    $stats[$pid] = self::emptyStats();
                }

                // Offense
                foreach (['pass', 'shot', 'dribble', 'special'] as $action) {
                    $stats[$pid]['offense'][$action]['attempts'] +=
                        $playerStats['offense'][$action]['attempts'] ?? 0;
                    $stats[$pid]['offense'][$action]['success']  +=
                        $playerStats['offense'][$action]['success']  ?? 0;
                }
                // Buts
                $stats[$pid]['offense']['goals'] =
                    ($stats[$pid]['offense']['goals'] ?? 0) + ($playerStats['offense']['goals'] ?? 0);

                // Defense
                foreach (['intercept', 'tackle', 'block', 'hands', 'punch', 'gkSpecial'] as $action) {
                    $stats[$pid]['defense'][$action]['attempts'] +=
                        $playerStats['defense'][$action]['attempts'] ?? 0;
                    $stats[$pid]['defense'][$action]['success']  +=
                        $playerStats['defense'][$action]['success']  ?? 0;
                }

                // Duels
                $stats[$pid]['duelsWon']  += $playerStats['duelsWon']  ?? 0;
                $stats[$pid]['duelsLost'] += $playerStats['duelsLost'] ?? 0;
            }
        }

        return $stats;
    }

    /**
     * Accumule les stats d'UN match (depuis playerActions JS)
     * dans game_saves.state.player_stats.
     *
     * Conservé pour compatibilité avec finishMatch() —
     * sera supprimé quand la simulation génèrera de vraies match_stats.
     */
    public static function accumulateFromActions(GameSave $gameSave, array $newActions): void
    {
        // On ne stocke plus dans state — les stats sont lues depuis game_matches.match_stats
        // Cette méthode est conservée pour ne pas casser finishMatch() en attendant
        // la refonte de la simulation.
    }

    public static function emptyStats(): array
    {
        return [
            'offense' => [
                'pass'    => ['attempts' => 0, 'success' => 0],
                'shot'    => ['attempts' => 0, 'success' => 0],
                'dribble' => ['attempts' => 0, 'success' => 0],
                'special' => ['attempts' => 0, 'success' => 0],
                'goals'   => 0,
            ],
            'defense' => [
                'intercept' => ['attempts' => 0, 'success' => 0],
                'tackle'    => ['attempts' => 0, 'success' => 0],
                'block'     => ['attempts' => 0, 'success' => 0],
                'hands'     => ['attempts' => 0, 'success' => 0],
                'punch'     => ['attempts' => 0, 'success' => 0],
                'gkSpecial' => ['attempts' => 0, 'success' => 0],
            ],
            'duelsWon'  => 0,
            'duelsLost' => 0,
        ];
    }
}

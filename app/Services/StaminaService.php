<?php

namespace App\Services;

use App\Models\GameSave;
use App\Models\GameTeam;

class StaminaService
{
    const MATCH_STAMINA_COST = 2;   // -2 stamina pour ceux qui ont joué
    const REST_STAMINA_RECOVERY = 10; // +10 stamina pour ceux qui ne jouent pas

    /**
     * Applique les règles de stamina d’après-match sur TOUTES les équipes de la GameSave.
     */
    public static function applyAfterMatch(GameSave $gameSave): void
    {
        $playerStats = $gameSave->state['player_stats'] ?? [];

        // 1. Récupération de toutes les équipes liées à la sauvegarde
        $teams = GameTeam::where('game_save_id', $gameSave->id)
            ->with('contracts.gamePlayer')
            ->get();

        foreach ($teams as $team) {
            foreach ($team->contracts as $c) {
                $player = $c->gamePlayer;

                if (!$player) {
                    continue;
                }

                $pStats = $playerStats[$player->id] ?? null;

                // 2. Ce joueur a-t-il joué ? (présent dans player_stats)
                if ($pStats && isset($pStats['duelsWon'])) {
                    // Il a joué → stamina -2
                    $player->stamina = max(0, $player->stamina - self::MATCH_STAMINA_COST);
                } else {
                    // Il n’a pas joué (remplaçant ou sans action) → stamina +10
                    $player->stamina = min(100, $player->stamina + self::REST_STAMINA_RECOVERY);
                }

                $player->save();
            }
        }
    }
}

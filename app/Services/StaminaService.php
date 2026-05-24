<?php

namespace App\Services;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;

class StaminaService
{
    const MATCH_STAMINA_COST     = 2;   // -2 stamina pour ceux qui ont joué
    const REST_STAMINA_RECOVERY  = 10;  // +10 stamina pour ceux qui n'ont pas joué

    /**
     * Applique les règles de stamina d'après-match sur TOUTES les équipes de la GameSave.
     * Source de vérité : game_matches.match_stats (joueurs ayant participé au match).
     */
    public static function applyAfterMatch(GameSave $gameSave, ?GameMatch $match = null): void
    {
        // Joueurs ayant joué = ceux présents dans match_stats.players du match
        $playedPlayerIds = [];

        if ($match && !empty($match->match_stats['players'])) {
            $playedPlayerIds = array_map('strval', array_keys($match->match_stats['players']));
        }

        // Toutes les équipes de la save
        $teams = GameTeam::where('game_save_id', $gameSave->id)
            ->with('contracts.gamePlayer')
            ->get();

        foreach ($teams as $team) {
            foreach ($team->contracts as $c) {
                $player = $c->gamePlayer;
                if (!$player) continue;

                $hasPlayed = in_array((string) $player->id, $playedPlayerIds, true);

                if ($hasPlayed) {
                    // A joué → stamina -2
                    $player->stamina = max(0, $player->stamina - self::MATCH_STAMINA_COST);
                } else {
                    // N'a pas joué → récupération +10
                    $player->stamina = min(100, $player->stamina + self::REST_STAMINA_RECOVERY);
                }

                $player->save();
            }
        }
    }
}

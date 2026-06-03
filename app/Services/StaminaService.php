<?php

namespace App\Services;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;

class StaminaService
{
    const MATCH_STAMINA_COST    = 2;   // -2 pour ceux qui ont joué
    const REST_STAMINA_RECOVERY = 10;  // +10 pour ceux qui n'ont pas joué

    /**
     * Applique stamina d'après-match pour UN match spécifique.
     * Conservé pour compatibilité — préférer applyAfterWeek().
     */
    public static function applyAfterMatch(GameSave $gameSave, ?GameMatch $match = null): void
    {
        if (!$match) return;

        $playedPlayerIds = self::resolvePlayedPlayerIds($match);

        self::applyStaminaChanges($gameSave, $playedPlayerIds);
    }

    /**
     * Applique stamina d'après-semaine en agrégeant TOUS les matchs joués
     * de la semaine donnée (source : match_stats, fallback : is_starter).
     */
    public static function applyAfterWeek(GameSave $gameSave, int $week): void
    {
        $matches = GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'played')
            ->get();

        $playedPlayerIds = [];
        foreach ($matches as $match) {
            foreach (self::resolvePlayedPlayerIds($match) as $pid) {
                $playedPlayerIds[$pid] = true;
            }
        }

        self::applyStaminaChanges($gameSave, array_keys($playedPlayerIds));
    }

    /**
     * Détermine les IDs joueurs ayant joué un match.
     * 1. Source primaire : match_stats.players (jeu manuel)
     * 2. Fallback : contrats is_starter des 2 équipes (match simulé sans stats)
     */
    protected static function resolvePlayedPlayerIds(GameMatch $match): array
    {
        if (!empty($match->match_stats['players'])) {
            return array_map('strval', array_keys($match->match_stats['players']));
        }

        // Fallback : titulaires des deux équipes
        $teamIds = array_filter([$match->home_team_id, $match->away_team_id]);
        if (empty($teamIds)) return [];

        return \App\Models\GameSaves\GameContract::query()
            ->whereIn('game_team_id', $teamIds)
            ->where('is_starter', true)
            ->pluck('game_player_id')
            ->map(fn($id) => (string) $id)
            ->all();
    }

    /**
     * Applique -2 aux joueurs ayant joué, +10 aux autres, sur toutes les
     * équipes de la save.
     */
    protected static function applyStaminaChanges(GameSave $gameSave, array $playedPlayerIds): void
    {
        $playedSet = array_flip($playedPlayerIds);

        $teams = GameTeam::where('game_save_id', $gameSave->id)
            ->with('contracts.gamePlayer')
            ->get();

        foreach ($teams as $team) {
            foreach ($team->contracts as $c) {
                $player = $c->gamePlayer;
                if (!$player) continue;

                $hasPlayed = isset($playedSet[(string) $player->id]);

                if ($hasPlayed) {
                    $player->stamina = max(0, $player->stamina - self::MATCH_STAMINA_COST);
                } else {
                    $player->stamina = min(100, $player->stamina + self::REST_STAMINA_RECOVERY);
                }

                $player->save();
            }
        }
    }
}

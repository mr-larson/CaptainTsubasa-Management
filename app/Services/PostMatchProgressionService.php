<?php

namespace App\Services;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use Illuminate\Support\Facades\DB;

class PostMatchProgressionService
{
    private const STAT_MAX_PER_MATCH    = 2;
    private const SEASON_CAP_PER_PLAYER = 40;
    private const STAT_MAX_VALUE        = 99;

    public function applyForMatch(GameSave $gameSave, GameMatch $match): array
    {
        $matchStats = $match->match_stats ?? [];
        $stats = $match->playerStatsMap();
        if (empty($stats)) return [];

        $season = (int) ($gameSave->season ?? 1);
        $state  = $gameSave->state ?? [];
        // Seule la saison en cours sert au plafond : les précédentes sont purgées.
        $state['season_progression'] = [$season => $state['season_progression'][$season] ?? []];

        $report = [];

        // Les ids viennent de match_stats (envoyé par le client pour un match
        // joué) : seuls les joueurs de CETTE save peuvent progresser.
        // Joueurs et équipes chargés en une fois (pas de requête par joueur).
        $playerIds = array_map('intval', array_keys($stats));
        $players   = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereIn('id', $playerIds)
            ->get()
            ->keyBy('id');

        $teamIdByPlayer = [];
        GameContract::whereIn('game_player_id', $players->keys())
            ->whereIn('game_team_id', [$match->home_team_id, $match->away_team_id])
            ->orderBy('id')
            ->get(['game_player_id', 'game_team_id'])
            ->each(function ($c) use (&$teamIdByPlayer) {
                $teamIdByPlayer[(int) $c->game_player_id] ??= (int) $c->game_team_id;
            });

        DB::transaction(function () use ($stats, $season, $match, $players, $teamIdByPlayer, &$state, &$report) {
            foreach ($stats as $playerId => $playerStats) {
                $playerId = (int) $playerId;
                $player   = $players->get($playerId);
                if (!$player || !is_array($playerStats)) continue;

                $alreadyGained = (int) ($state['season_progression'][$season][$playerId] ?? 0);
                if ($alreadyGained >= self::SEASON_CAP_PER_PLAYER) continue;

                $remainingCap = self::SEASON_CAP_PER_PLAYER - $alreadyGained;
                $gains        = $this->computeGains($playerStats);

                // Le moral module la progression (un joueur frustré apprend moins vite)
                $moraleXpFactor = MoraleService::xpFactor($player->morale ?? null);
                if ($moraleXpFactor !== 1.0) {
                    $gains = array_filter(array_map(
                        fn($g) => (int) round($g * $moraleXpFactor),
                        $gains
                    ));
                }

                if (empty($gains)) continue;

                $totalRequested = array_sum($gains);
                if ($totalRequested > $remainingCap) {
                    $ratio = $remainingCap / max(1, $totalRequested);
                    foreach ($gains as $stat => $g) {
                        $gains[$stat] = (int) floor($g * $ratio);
                    }
                }

                $applied      = [];
                $totalApplied = 0;
                foreach ($gains as $stat => $g) {
                    if ($g <= 0) continue;
                    $old = (int) ($player->{$stat} ?? 0);
                    $new = min(self::STAT_MAX_VALUE, $old + $g);
                    $realGain = $new - $old;
                    if ($realGain <= 0) continue;
                    $player->{$stat} = $new;
                    $applied[$stat]  = ['old' => $old, 'new' => $new, 'gain' => $realGain];
                    $totalApplied   += $realGain;
                }

                if ($totalApplied > 0) {
                    $player->save();
                    $state['season_progression'][$season][$playerId] = $alreadyGained + $totalApplied;

                    // Détermine l'équipe et le side (home/away) pour affichage UI
                    $teamId   = $teamIdByPlayer[$playerId] ?? null;
                    $teamSide = $teamId === (int) $match->home_team_id ? 'home' : 'away';

                    $report[] = [
                        'player_id'    => $playerId,
                        'lastname'     => $player->lastname,
                        'firstname'    => $player->firstname,
                        'photo_path'   => $player->photo_path,
                        'number'       => $player->number,
                        'team_side'    => $teamSide,
                        'team_id'      => $teamId,
                        'match_id'     => $match->id,
                        'gains'        => $applied,
                        'total'        => $totalApplied,
                        'season_total' => $state['season_progression'][$season][$playerId],
                    ];
                }
            }
        });

        $history = $state['match_progression_history'] ?? [];
        $history[] = [
            'match_id' => $match->id,
            'week'     => $match->week,
            'season'   => $season,
            'count'    => count($report),
        ];
        $state['match_progression_history'] = array_slice($history, -50);

        $gameSave->state = $state;
        $gameSave->save();

        // Persister dans match_stats.progression pour l'affichage dans TabCalendar
        $matchStats['progression'] = $report;
        $match->match_stats = $matchStats;
        $match->save();

        return $report;
    }

    private function computeGains(array $playerStats): array
    {
        $offense = $playerStats['offense'] ?? [];
        $defense = $playerStats['defense'] ?? [];
        $gains   = [];

        $shotScore = (($offense['goals'] ?? 0) * 1.0)
            + (($offense['shot']['success']    ?? 0) * 0.4)
            + (($offense['special']['success'] ?? 0) * 0.4);
        $shot = (int) min(self::STAT_MAX_PER_MATCH, round($shotScore));
        if ($shot > 0) $gains['shot'] = $shot;

        $passScore = ($offense['pass']['success'] ?? 0) * 0.3
            + ($offense['cross']['success'] ?? 0) * 0.3
            + ($offense['long_pass']['success'] ?? 0) * 0.3;
        $pass = (int) min(self::STAT_MAX_PER_MATCH, round($passScore));
        if ($pass > 0) $gains['pass'] = $pass;

        $dribbleScore = ($offense['dribble']['success'] ?? 0) * 0.5;
        $dribble = (int) min(self::STAT_MAX_PER_MATCH, round($dribbleScore));
        if ($dribble > 0) $gains['dribble'] = $dribble;

        $defScore = (($defense['tackle']['success']    ?? 0)
                + ($defense['intercept']['success'] ?? 0)
                + ($defense['block']['success']     ?? 0)
                + ($defense['hands']['success']     ?? 0)
                + ($defense['punch']['success']     ?? 0)
                + ($defense['gkSpecial']['success'] ?? 0)) * 0.4;
        $defGain = (int) min(self::STAT_MAX_PER_MATCH, round($defScore));
        if ($defGain > 0) $gains['defense'] = $defGain;

        return $gains;
    }
}

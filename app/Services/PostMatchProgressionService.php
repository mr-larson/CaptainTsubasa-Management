<?php

namespace App\Services;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use Illuminate\Support\Facades\DB;

/** Progression des joueurs après un match : les bonnes actions (tirs, passes, dribbles, défense) rapportent des points de stats, modulés par le moral et plafonnés par match, par saison et par stat (99). */
class PostMatchProgressionService
{
    /** Gain maximum par stat et par match. */
    private const STAT_MAX_PER_MATCH    = 2;
    /** Total maximum de points gagnés par un joueur sur une saison. */
    private const SEASON_CAP_PER_PLAYER = 40;
    /** Valeur maximale d'une stat. */
    private const STAT_MAX_VALUE        = 99;

    /**
     * Applique la progression d'un match joué : calcule les gains de chaque joueur de la sauvegarde, les écrit en base (transaction), met à jour le cumul de saison dans l'état de la partie, puis stocke le rapport dans match_stats.progression. Retourne ce rapport.
     * À appeler sous le verrou de la sauvegarde : modifie et enregistre la partie et le match.
     */
    public function applyForMatch(GameSave $gameSave, GameMatch $match): array
    {
        $matchStats = $match->match_stats ?? [];
        $stats = $match->playerStatsMap();
        if (empty($stats)) return [];

        $season = (int) ($gameSave->season ?? 1);
        $state  = $gameSave->gameState();
        // Seule la saison en cours sert au plafond : les précédentes sont purgées.
        $state->keepOnlySeasonProgression($season);

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

        DB::transaction(function () use ($stats, $season, $match, $players, $teamIdByPlayer, $state, &$report) {
            foreach ($stats as $playerId => $playerStats) {
                $playerId = (int) $playerId;
                $player   = $players->get($playerId);
                if (!$player || !is_array($playerStats)) continue;

                $alreadyGained = $state->playerSeasonProgression($season, $playerId);
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
                    $state->setPlayerSeasonProgression($season, $playerId, $alreadyGained + $totalApplied);

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
                        'season_total' => $alreadyGained + $totalApplied,
                    ];
                }
            }
        });

        $state->recordMatchProgression([
            'match_id' => $match->id,
            'week'     => $match->week,
            'season'   => $season,
            'count'    => count($report),
        ]);
        $gameSave->save();

        // Persister dans match_stats.progression pour l'affichage dans TabCalendar
        $matchStats['progression'] = $report;
        $match->match_stats = $matchStats;
        $match->save();

        return $report;
    }

    /** Gains bruts d'un joueur d'après ses stats de match : tir (buts + tirs et techniques réussis), passe, dribble et défense, chacun plafonné par match. Ne renvoie que les stats à gain positif. */
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

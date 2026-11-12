<?php

namespace App\Services;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;

/**
 * Classements d'une journée (semaine) : hommes du match, buteurs, passeurs,
 * dribbleurs, gardiens, défenseurs, duels — calculés sur les match_stats des
 * matchs joués cette semaine-là.
 */
class WeekLeadersService
{
    public const LIMIT = 5;

    /**
     * @return array{week:int, matches:int, categories: array<string, array<int, array>>, players?: array<int, GamePlayer>}
     */
    public function forWeek(GameSave $gameSave, int $week): array
    {
        $matches = GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'played')
            ->whereNotNull('match_stats')
            ->with('playerStats')
            ->get();

        $rows   = [];   // une ligne par joueur et par match
        $motm   = [];   // homme du match de chaque rencontre
        $sideBy = [];   // match_id => [player_id => team_id]

        foreach ($matches as $match) {
            $stats   = $match->match_stats ?? [];
            $players = $match->playerStatsMap();
            $sideOf  = $this->sides($match, $stats, array_keys($players));
            $sideBy[$match->id] = $sideOf;
            $best    = null;

            foreach ($players as $pid => $s) {
                $pid  = (int) $pid;
                $o    = $s['offense'] ?? [];
                $d    = $s['defense'] ?? [];
                $row  = [
                    'player_id' => $pid,
                    'team_id'   => $sideOf[$pid] ?? null,
                    'match_id'  => $match->id,
                    'goals'     => (int) ($o['goals'] ?? 0),
                    'passes'    => (int) ($o['pass']['success'] ?? 0),
                    'dribbles'  => (int) ($o['dribble']['success'] ?? 0),
                    'saves'     => (int) (($d['hands']['success'] ?? 0) + ($d['punch']['success'] ?? 0) + ($d['gkSpecial']['success'] ?? 0)),
                    'defense'   => (int) (($d['intercept']['success'] ?? 0) + ($d['tackle']['success'] ?? 0) + ($d['block']['success'] ?? 0)),
                    'duelsWon'  => (int) ($s['duelsWon'] ?? 0),
                    'duelsLost' => (int) ($s['duelsLost'] ?? 0),
                    'shots'     => (int) ($o['shot']['success'] ?? 0),
                ];
                $row['rating'] = $this->rating($row);
                $rows[] = $row;

                if (!$best || $row['rating'] > $best['rating'] || ($row['rating'] === $best['rating'] && $row['goals'] > $best['goals'])) {
                    $best = $row;
                }
            }

            if ($best && $best['rating'] > 0) {
                $motm[] = $best;
            }
        }

        // Fautes : cartons et blessures enregistrés pour les matchs de la semaine
        // (les fautes elles-mêmes ne sont pas conservées joueur par joueur).
        $matchIds  = $matches->pluck('id')->all();
        $incidents = [];
        $CARD = [
            'red'           => ['rank' => 3, 'icon' => '🟥', 'label' => 'Carton rouge'],
            'double_yellow' => ['rank' => 2, 'icon' => '🟨🟥', 'label' => 'Deux jaunes'],
            'yellow'        => ['rank' => 1, 'icon' => '🟨', 'label' => 'Carton jaune'],
        ];
        foreach (GameSanction::whereIn('game_match_id', $matchIds)->get() as $s) {
            $card = $CARD[$s->type] ?? $CARD['yellow'];
            $incidents[] = [
                'player_id' => (int) $s->game_player_id, 'match_id' => (int) $s->game_match_id,
                'rank' => $card['rank'] + 10, 'icon' => $card['icon'],
                'detail' => $card['label'] . ($s->weeks_suspended ? " — suspendu {$s->weeks_suspended} sem." : ''),
            ];
        }
        foreach (GameInjury::whereIn('game_match_id', $matchIds)->get() as $i) {
            $incidents[] = [
                'player_id' => (int) $i->game_player_id, 'match_id' => (int) $i->game_match_id,
                'rank' => (int) $i->weeks_out, 'icon' => '🤕',
                'detail' => 'Blessé' . ($i->description ? " : {$i->description}" : '') . " — {$i->weeks_out} sem.",
            ];
        }
        usort($incidents, fn ($a, $b) => $b['rank'] <=> $a['rank']);

        $players = GamePlayer::where('game_save_id', $gameSave->id)->whereIn('id', array_unique(array_merge(array_column($rows, 'player_id'), array_column($incidents, 'player_id'))))
            ->get()
            ->keyBy('id');

        $present = function (array $row, string $metric, ?string $detail = null) use ($players) {
            $p = $players[$row['player_id']] ?? null;
            return [
                'player_id'  => $row['player_id'],
                'lastname'   => $p?->lastname ?? '?',
                'firstname'  => $p?->firstname,
                'photo_path' => $p?->photo_path,
                'team_id'    => $row['team_id'],
                'match_id'   => $row['match_id'],
                'value'      => $metric === 'rating' ? round($row['rating'], 1) : $row[$metric],
                'detail'     => $detail,
            ];
        };

        $top = function (string $metric, ?callable $detail = null) use ($rows, $present) {
            $list = array_filter($rows, fn ($r) => $r[$metric] > 0);
            usort($list, fn ($a, $b) => [$b[$metric], $b['rating']] <=> [$a[$metric], $a['rating']]);
            return array_map(fn ($r) => $present($r, $metric, $detail ? $detail($r) : null), array_slice($list, 0, self::LIMIT));
        };

        usort($motm, fn ($a, $b) => $b['rating'] <=> $a['rating']);

        $categories = [
            'motm'     => array_map(fn ($r) => $present($r, 'rating', $this->motmDetail($r)), $motm),
            'scorers'  => $top('goals'),
            'passers'  => $top('passes'),
            'dribblers'=> $top('dribbles'),
            'keepers'  => $top('saves'),
            'defenders'=> $top('defense'),
            'duels'    => $top('duelsWon', function ($r) {
                $total = $r['duelsWon'] + $r['duelsLost'];
                return $total ? round($r['duelsWon'] * 100 / $total) . ' %' : null;
            }),
            'fouls'    => array_map(function ($inc) use ($players, $sideBy) {
                $p = $players[$inc['player_id']] ?? null;
                return [
                    'player_id'  => $inc['player_id'],
                    'lastname'   => $p?->lastname ?? '?',
                    'firstname'  => $p?->firstname,
                    'photo_path' => $p?->photo_path,
                    'team_id'    => $sideBy[$inc['match_id']][$inc['player_id']] ?? null,
                    'match_id'   => $inc['match_id'],
                    'value'      => $inc['icon'],
                    'detail'     => $inc['detail'],
                ];
            }, $incidents),
        ];

        $rankedPlayerIds = array_column(array_merge(...array_values($categories)), 'player_id');
        $rankedPlayers = $players->only($rankedPlayerIds)->keyBy('id');
        $rankedPlayers->filter(fn (GamePlayer $player) => blank($player->description))
            ->load('basePlayer:id,description');

        return [
            'week'       => $week,
            'matches'    => $matches->count(),
            'categories' => $categories,
            'players'    => $rankedPlayers->map(fn (GamePlayer $player) => [
                ...$player->attributesToArray(),
                'description' => filled($player->description) ? $player->description : $player->basePlayer?->description,
            ])->all(),
        ];
    }

    /**
     * Note de match — même barème que l'homme du match de l'onglet Match
     * (TabMatch.vue › manOfTheMatch).
     */
    private function rating(array $r): float
    {
        return $r['goals'] * 4 + $r['shots'] + $r['dribbles'] + $r['passes'] * 0.5
            + $r['defense'] + $r['saves'] * 1.5 + $r['duelsWon'] - $r['duelsLost'] * 0.5;
    }

    private function motmDetail(array $r): string
    {
        $bits = [];
        if ($r['goals'])    $bits[] = $r['goals'] . ' but' . ($r['goals'] > 1 ? 's' : '');
        if ($r['saves'])    $bits[] = $r['saves'] . ' arrêt' . ($r['saves'] > 1 ? 's' : '');
        if ($r['dribbles']) $bits[] = $r['dribbles'] . ' dribble' . ($r['dribbles'] > 1 ? 's' : '');
        if ($r['duelsWon']) $bits[] = $r['duelsWon'] . ' duel' . ($r['duelsWon'] > 1 ? 's' : '') . ' gagné' . ($r['duelsWon'] > 1 ? 's' : '');
        return implode(', ', array_slice($bits, 0, 3));
    }

    /**
     * Équipe de chaque joueur pour ce match : instantané des effectifs, titulaires
     * notés par camp, sinon contrats actifs cette semaine-là.
     *
     * @return array<int, int> player_id => team_id
     */
    private function sides(GameMatch $match, array $stats, array $playerIds): array
    {
        $map = [];
        foreach (['home' => $match->home_team_id, 'away' => $match->away_team_id] as $side => $teamId) {
            foreach ($stats['lineups'][$side]['players'] ?? [] as $p) {
                $map[(int) ($p['id'] ?? 0)] = (int) $teamId;
            }
            foreach ($stats['starters'][$side] ?? [] as $pid) {
                $map[(int) $pid] ??= (int) $teamId;
            }
        }

        $missing = array_diff(array_map('intval', $playerIds), array_keys($map));
        if ($missing) {
            GameContract::where('game_save_id', $match->game_save_id)
                ->whereIn('game_team_id', [$match->home_team_id, $match->away_team_id])
                ->whereIn('game_player_id', $missing)
                ->activeAt((int) $match->week)
                ->get(['game_player_id', 'game_team_id'])
                ->each(function ($c) use (&$map) { $map[(int) $c->game_player_id] = (int) $c->game_team_id; });
        }

        return $map;
    }
}

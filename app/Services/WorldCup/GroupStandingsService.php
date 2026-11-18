<?php

namespace App\Services\WorldCup;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;

/**
 * Classement des poules de la Coupe du Monde, calculé depuis les matchs joués.
 *
 * Domaine : Coupe du Monde. Utilisé par TournamentService (qualifiés, présentation des poules) et par
 * KnockoutService via les meilleurs troisièmes. Ordre de départage, comme au Mondial :
 *   1. points ;
 *   2. confrontations directes entre les ex æquo (points, différence de buts, buts marqués entre eux) ;
 *   3. différence de buts générale, puis buts marqués ;
 *   4. fair-play (jaune −1, deux jaunes −3, rouge −4 : le moins pénalisé passe devant) ;
 *   5. tirage au sort STABLE : un ordre fixe tiré de l'id de la partie et de l'équipe, qui ne change pas
 *      d'un affichage à l'autre (pas d'aléatoire à chaque calcul).
 */
class GroupStandingsService
{
    /** Points de fair-play retirés par type de carton. */
    private const FAIR_PLAY = ['yellow' => -1, 'double_yellow' => -3, 'red' => -4];

    /**
     * Classement complet d'un groupe, du 1ᵉʳ au dernier.
     *
     * @param  array<int, int>  $teamIds
     * @return array<int, array<string, mixed>> lignes : team_id, name, played, wins, draws, losses, gf, ga, points, gd, fair_play
     */
    public function table(GameSave $gameSave, array $teamIds, string $round): array
    {
        $teams = GameTeam::where('game_save_id', $gameSave->id)->whereIn('id', $teamIds)->get()->keyBy('id');
        $matches = GameMatch::where('game_save_id', $gameSave->id)->where('round', $round)->where('status', 'played')->get();
        $fairPlay = $this->fairPlayByTeam($gameSave, $matches);

        $rows = [];
        foreach ($teamIds as $tid) {
            $rows[$tid] = [
                'team_id' => $tid, 'name' => $teams[$tid]->name ?? '?',
                'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'gf' => 0, 'ga' => 0,
            ];
        }

        foreach ($matches as $m) {
            $h = $m->home_team_id;
            $a = $m->away_team_id;
            if (! isset($rows[$h], $rows[$a])) {
                continue;
            }
            $hs = (int) $m->home_score;
            $as = (int) $m->away_score;
            $rows[$h]['played']++;
            $rows[$a]['played']++;
            $rows[$h]['gf'] += $hs;
            $rows[$h]['ga'] += $as;
            $rows[$a]['gf'] += $as;
            $rows[$a]['ga'] += $hs;
            if ($hs > $as) {
                $rows[$h]['wins']++;
                $rows[$a]['losses']++;
            } elseif ($hs < $as) {
                $rows[$a]['wins']++;
                $rows[$h]['losses']++;
            } else {
                $rows[$h]['draws']++;
                $rows[$a]['draws']++;
            }
        }

        $rows = array_values($rows);
        foreach ($rows as &$r) {
            $r['points'] = $r['wins'] * 3 + $r['draws'];
            $r['gd'] = $r['gf'] - $r['ga'];
            $r['fair_play'] = $fairPlay[$r['team_id']] ?? 0;
        }
        unset($r);

        usort($rows, fn ($x, $y) => $this->generalKey($y, $gameSave) <=> $this->generalKey($x, $gameSave));

        return $this->applyHeadToHead($rows, $matches);
    }

    /**
     * Classement réduit aux ids d'équipes (du 1ᵉʳ au dernier).
     *
     * @param  array<int, int>  $teamIds
     * @return array<int, int>
     */
    public function ranking(GameSave $gameSave, array $teamIds, string $round): array
    {
        return array_column($this->table($gameSave, $teamIds, $round), 'team_id');
    }

    /**
     * Meilleurs troisièmes de tous les groupes (format à 24 : 4 troisièmes qualifiés), classés selon
     * points, différence de buts, buts marqués, fair-play puis tirage au sort stable.
     *
     * @param  array<string, array<int, int>>  $groups  groupe => ids d'équipes
     * @return array<int, int> ids des $take meilleurs troisièmes, du meilleur au moins bon
     */
    public function bestThirds(GameSave $gameSave, array $groups, int $take): array
    {
        $thirds = [];
        foreach ($groups as $key => $teamIds) {
            $row = $this->table($gameSave, $teamIds, 'group_'.strtolower($key))[2] ?? null;
            if ($row) {
                $thirds[] = $row;
            }
        }
        usort($thirds, fn ($x, $y) => $this->generalKey($y, $gameSave) <=> $this->generalKey($x, $gameSave));

        return array_slice(array_column($thirds, 'team_id'), 0, $take);
    }

    /**
     * Clé de tri général (plus grand = mieux classé) : points, diff, buts, fair-play, puis tirage stable.
     *
     * @param  array<string, mixed>  $row
     * @return array<int, int>
     */
    private function generalKey(array $row, GameSave $gameSave): array
    {
        return [$row['points'], $row['gd'], $row['gf'], $row['fair_play'], -$this->lot($gameSave, (int) $row['team_id'])];
    }

    /** Tirage au sort stable : même résultat à chaque appel pour une partie et une équipe données. */
    private function lot(GameSave $gameSave, int $teamId): int
    {
        return crc32($gameSave->id.'-'.$teamId);
    }

    /**
     * Départage les équipes à égalité de points par leurs confrontations directes (points, diff, buts
     * entre elles) ; à confrontations égales, l'ordre général (diff, buts, fair-play, tirage) est conservé
     * (usort est stable depuis PHP 8).
     *
     * @param  array<int, array<string, mixed>>  $rows  lignes déjà triées par clé générale
     * @param  Collection<int, GameMatch>  $matches  matchs joués du groupe
     * @return array<int, array<string, mixed>>
     */
    private function applyHeadToHead(array $rows, Collection $matches): array
    {
        $result = [];
        foreach (collect($rows)->groupBy('points') as $tied) {
            $tied = $tied->values()->all();
            if (count($tied) > 1) {
                $mini = $this->miniLeague(array_column($tied, 'team_id'), $matches);
                usort($tied, fn ($x, $y) => $mini[$y['team_id']] <=> $mini[$x['team_id']]);
            }
            array_push($result, ...$tied);
        }

        return $result;
    }

    /**
     * Mini-classement entre des équipes à égalité : [points, diff, buts] de leurs seuls matchs communs.
     *
     * @param  array<int, int>  $ids
     * @return array<int, array{0:int,1:int,2:int}> team_id => [pts, gd, gf]
     */
    private function miniLeague(array $ids, Collection $matches): array
    {
        $mini = array_fill_keys($ids, [0, 0, 0]);
        foreach ($matches as $m) {
            $h = $m->home_team_id;
            $a = $m->away_team_id;
            if (! isset($mini[$h], $mini[$a])) {
                continue;
            }
            $hs = (int) $m->home_score;
            $as = (int) $m->away_score;
            $mini[$h][1] += $hs - $as;
            $mini[$h][2] += $hs;
            $mini[$a][1] += $as - $hs;
            $mini[$a][2] += $as;
            if ($hs > $as) {
                $mini[$h][0] += 3;
            } elseif ($hs < $as) {
                $mini[$a][0] += 3;
            } else {
                $mini[$h][0]++;
                $mini[$a][0]++;
            }
        }

        return $mini;
    }

    /**
     * Points de fair-play (≤ 0) de chaque équipe sur ces matchs : cartons de ses joueurs.
     *
     * @param  Collection<int, GameMatch>  $matches
     * @return array<int, int> team_id => points de fair-play
     */
    private function fairPlayByTeam(GameSave $gameSave, Collection $matches): array
    {
        if ($matches->isEmpty()) {
            return [];
        }

        $sanctions = GameSanction::where('game_save_id', $gameSave->id)
            ->whereIn('game_match_id', $matches->pluck('id'))->get(['game_player_id', 'type']);
        if ($sanctions->isEmpty()) {
            return [];
        }

        $teamOf = GameContract::where('game_save_id', $gameSave->id)
            ->whereIn('game_player_id', $sanctions->pluck('game_player_id'))->pluck('game_team_id', 'game_player_id');

        $points = [];
        foreach ($sanctions as $s) {
            $team = $teamOf[$s->game_player_id] ?? null;
            if ($team !== null) {
                $points[$team] = ($points[$team] ?? 0) + (self::FAIR_PLAY[$s->type] ?? 0);
            }
        }

        return $points;
    }
}

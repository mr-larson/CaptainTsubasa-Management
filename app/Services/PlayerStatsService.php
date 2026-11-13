<?php

namespace App\Services;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameMatchPlayerStat;
use App\Models\GameSaves\GameSave;

/** Statistiques de match des joueurs d'une sauvegarde : agrégation SQL depuis game_match_player_stats (avec cache), structure vide de référence, et récupération des anciens bilans. */
class PlayerStatsService
{
    /**
     * Agrège en SQL les stats de tous les matchs joués d'une save
     * depuis game_match_player_stats (une ligne par joueur et par match).
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
    /**
     * aggregateForSave() mis en cache : les stats ne changent qu'à la fin d'un
     * match, donc la clé suit le nombre de matchs joués et le dernier id. La
     * page Play ne refait plus l'agrégation à chaque affichage.
     */
    public static function cachedAggregateForSave(GameSave $gameSave): array
    {
        $played = GameMatch::where('game_save_id', $gameSave->id)->where('status', 'played')
            ->selectRaw('COUNT(*) AS n, MAX(id) AS last_id')->toBase()->first();

        $key = sprintf('save-stats:%d:s%d:%d:%d', $gameSave->id, (int) ($gameSave->season ?? 1), $played->n, $played->last_id);

        return \Illuminate\Support\Facades\Cache::remember($key, now()->addDay(), fn () => self::aggregateForSave($gameSave));
    }

    /** Agrège en SQL, pour chaque joueur, les compteurs (passes, tirs, dribbles, buts, interceptions, duels...) de tous les matchs joués de la sauvegarde. Le format est décrit dans le commentaire du dessus. */
    public static function aggregateForSave(GameSave $gameSave): array
    {
        $offense = ['pass', 'shot', 'dribble', 'special', 'cross'];
        $defense = GameMatchPlayerStat::DEFENSE;

        $sums = ['SUM(goals) AS goals', 'SUM(duels_won) AS duels_won', 'SUM(duels_lost) AS duels_lost'];
        foreach ([...$offense, ...array_values($defense)] as $prefix) {
            $sums[] = "SUM({$prefix}_attempts) AS {$prefix}_attempts";
            $sums[] = "SUM({$prefix}_success) AS {$prefix}_success";
        }

        $rows = GameMatchPlayerStat::query()
            ->where('game_save_id', $gameSave->id)
            ->whereIn('game_match_id', GameMatch::where('game_save_id', $gameSave->id)->where('status', 'played')->select('id'))
            ->groupBy('game_player_id')
            ->selectRaw('game_player_id, ' . implode(', ', $sums))
            ->toBase()
            ->get();

        $stats = [];
        foreach ($rows as $row) {
            $player = self::emptyStats();
            foreach ($offense as $action) {
                $player['offense'][$action] = [
                    'attempts' => (int) $row->{"{$action}_attempts"},
                    'success'  => (int) $row->{"{$action}_success"},
                ];
            }
            foreach ($defense as $action => $prefix) {
                $player['defense'][$action] = [
                    'attempts' => (int) $row->{"{$prefix}_attempts"},
                    'success'  => (int) $row->{"{$prefix}_success"},
                ];
            }
            $player['offense']['goals'] = (int) $row->goals;
            $player['duelsWon']         = (int) $row->duels_won;
            $player['duelsLost']        = (int) $row->duels_lost;

            $stats[(string) $row->game_player_id] = $player;
        }

        return $stats;
    }

    /**
     * Récupère les compteurs absents des anciens bilans à partir des duels
     * conservés dans le déroulé, sans remplacer les compteurs déjà enregistrés.
     * Ne sert plus qu'à la migration qui range `players` et `events` dans
     * leurs tables : les bilans stockés ensuite sont complets.
     */
    public static function withCrossAndHeadingStats(?array $stats): ?array
    {
        if ($stats === null) {
            return null;
        }

        $recovered = [];
        foreach ($stats['events'] ?? [] as $event) {
            if (($event['action'] ?? null) !== 'cross' || ($event['def_action'] ?? null) !== 'heading') {
                continue;
            }

            $winner = $event['meta']['result']['winner'] ?? $event['result'] ?? null;
            if (!in_array($winner, ['attack', 'defense', 'tie'], true)) {
                continue;
            }

            foreach ([
                ['attacker', 'offense', 'cross', 'attack'],
                ['defender', 'defense', 'heading', 'defense'],
            ] as [$actor, $side, $action, $success]) {
                $id = $event[$actor]['id'] ?? null;
                if ($id === null || isset($stats['players'][$id][$side][$action])) {
                    continue;
                }

                $recovered[$id][$side][$action] ??= ['attempts' => 0, 'success' => 0];
                $recovered[$id][$side][$action]['attempts']++;
                if ($winner === $success) {
                    $recovered[$id][$side][$action]['success']++;
                }
            }
        }

        foreach ($recovered as $id => $sides) {
            foreach ($sides as $side => $actions) {
                foreach ($actions as $action => $counts) {
                    $stats['players'][$id][$side][$action] = $counts;
                }
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

    /** Structure de stats d'un joueur avec tous les compteurs à zéro (offense, défense, duels gagnés/perdus). */
    public static function emptyStats(): array
    {
        return [
            'offense' => [
                'pass'    => ['attempts' => 0, 'success' => 0],
                'shot'    => ['attempts' => 0, 'success' => 0],
                'dribble' => ['attempts' => 0, 'success' => 0],
                'special' => ['attempts' => 0, 'success' => 0],
                'cross'   => ['attempts' => 0, 'success' => 0],
                'goals'   => 0,
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
}

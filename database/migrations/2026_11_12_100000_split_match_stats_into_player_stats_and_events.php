<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sort de game_matches.match_stats ses deux parties lourdes :
 *  - `players` → game_match_player_stats (une ligne par joueur et par match) ;
 *  - `events`  → game_match_events (1-1, chargée seulement sur la feuille de match).
 * La colonne ne garde que le résumé (teams, starters, playtime, lineups…).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_match_player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_save_id')->constrained('game_saves')->cascadeOnDelete();
            $table->foreignId('game_match_id')->constrained('game_matches')->cascadeOnDelete();
            // Pas de clé étrangère : le bilan survit au joueur, comme dans le JSON.
            $table->unsignedBigInteger('game_player_id');

            foreach (self::counterColumns() as $column) {
                $table->unsignedSmallInteger($column)->default(0);
            }

            $table->unique(['game_match_id', 'game_player_id']);
            $table->index(['game_save_id', 'game_player_id']);
        });

        Schema::create('game_match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_match_id')->unique()->constrained('game_matches')->cascadeOnDelete();
            $table->json('events');
        });

        DB::table('game_matches')
            ->whereNotNull('match_stats')
            ->select(['id', 'game_save_id', 'match_stats'])
            ->chunkById(25, function ($matches) {
                foreach ($matches as $match) {
                    $stats = json_decode($match->match_stats, true);
                    if (! is_array($stats) || (! array_key_exists('players', $stats) && ! array_key_exists('events', $stats))) {
                        continue;
                    }

                    // Anciens bilans : centres et têtes retrouvés une fois pour
                    // toutes dans le déroulé, avant de s'en séparer.
                    $stats = self::withCrossAndHeadingStats($stats);

                    $rows = [];
                    foreach (is_array($stats['players'] ?? null) ? $stats['players'] : [] as $playerId => $playerStats) {
                        if ((int) $playerId <= 0) {
                            continue;
                        }
                        $rows[] = [
                            'game_save_id'   => $match->game_save_id,
                            'game_match_id'  => $match->id,
                            'game_player_id' => (int) $playerId,
                        ] + self::columnsFromStats($playerStats);
                    }
                    $events = is_array($stats['events'] ?? null) ? array_values($stats['events']) : [];
                    unset($stats['players'], $stats['events']);

                    DB::transaction(function () use ($match, $rows, $events, $stats) {
                        foreach (array_chunk($rows, 100) as $chunk) {
                            DB::table('game_match_player_stats')->insert($chunk);
                        }
                        if ($events) {
                            DB::table('game_match_events')->insert([
                                'game_match_id' => $match->id,
                                'events'        => json_encode($events),
                            ]);
                        }
                        DB::table('game_matches')->where('id', $match->id)->update(['match_stats' => json_encode($stats)]);
                    });
                }
            });

        // Rend au disque l'espace libéré par les JSON raccourcis.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('OPTIMIZE TABLE game_matches');
        }
    }

    public function down(): void
    {
        DB::table('game_matches')
            ->whereNotNull('match_stats')
            ->select(['id', 'match_stats'])
            ->chunkById(25, function ($matches) {
                foreach ($matches as $match) {
                    $stats = json_decode($match->match_stats, true);
                    if (! is_array($stats)) {
                        continue;
                    }

                    $players = [];
                    foreach (DB::table('game_match_player_stats')->where('game_match_id', $match->id)->get() as $row) {
                        $players[$row->game_player_id] = self::statsFromColumns($row);
                    }
                    $events = DB::table('game_match_events')->where('game_match_id', $match->id)->value('events');

                    if ($players) {
                        $stats['players'] = $players;
                    }
                    if ($events) {
                        $stats['events'] = json_decode($events, true);
                    }
                    if ($players || $events) {
                        DB::table('game_matches')->where('id', $match->id)->update(['match_stats' => json_encode($stats)]);
                    }
                }
            });

        Schema::dropIfExists('game_match_events');
        Schema::dropIfExists('game_match_player_stats');
    }

    // ── Copies figées des helpers de l'application (la migration ne dépend pas du
    //    code courant : un migrate:fresh futur ne casse pas si ces classes évoluent).

    private const OFFENSE = ['pass' => 'pass', 'shot' => 'shot', 'dribble' => 'dribble', 'special' => 'special', 'cross' => 'cross', 'long_pass' => 'long_pass'];
    private const DEFENSE = ['intercept' => 'intercept', 'tackle' => 'tackle', 'block' => 'block', 'heading' => 'heading', 'hands' => 'hands', 'punch' => 'punch', 'gkSpecial' => 'gk_special'];

    private static function counterColumns(): array
    {
        $columns = [];
        foreach ([...array_values(self::OFFENSE), ...array_values(self::DEFENSE)] as $prefix) {
            $columns[] = "{$prefix}_attempts";
            $columns[] = "{$prefix}_success";
        }

        return [...$columns, 'goals', 'duels_won', 'duels_lost'];
    }

    private static function columnsFromStats(mixed $stats): array
    {
        $stats = is_array($stats) ? $stats : [];
        $int   = fn ($v) => max(0, min(65535, (int) (is_numeric($v) ? $v : 0)));

        $columns = [];
        foreach (['offense' => self::OFFENSE, 'defense' => self::DEFENSE] as $side => $actions) {
            foreach ($actions as $key => $prefix) {
                $columns["{$prefix}_attempts"] = $int($stats[$side][$key]['attempts'] ?? 0);
                $columns["{$prefix}_success"]  = $int($stats[$side][$key]['success'] ?? 0);
            }
        }
        $columns['goals']      = $int($stats['offense']['goals'] ?? 0);
        $columns['duels_won']  = $int($stats['duelsWon'] ?? 0);
        $columns['duels_lost'] = $int($stats['duelsLost'] ?? 0);

        return $columns;
    }

    private static function statsFromColumns(object $row): array
    {
        $row   = (array) $row;
        $stats = ['offense' => [], 'defense' => []];
        foreach (['offense' => self::OFFENSE, 'defense' => self::DEFENSE] as $side => $actions) {
            foreach ($actions as $key => $prefix) {
                $stats[$side][$key] = ['attempts' => (int) ($row["{$prefix}_attempts"] ?? 0), 'success' => (int) ($row["{$prefix}_success"] ?? 0)];
            }
        }
        $stats['offense']['goals'] = (int) ($row['goals'] ?? 0);
        $stats['duelsWon']         = (int) ($row['duels_won'] ?? 0);
        $stats['duelsLost']        = (int) ($row['duels_lost'] ?? 0);

        return $stats;
    }

    /** Récupère centres / têtes absents des anciens bilans à partir du déroulé. */
    private static function withCrossAndHeadingStats(array $stats): array
    {
        $recovered = [];
        foreach ($stats['events'] ?? [] as $event) {
            if (($event['action'] ?? null) !== 'cross' || ($event['def_action'] ?? null) !== 'heading') continue;
            $winner = $event['meta']['result']['winner'] ?? $event['result'] ?? null;
            if (!in_array($winner, ['attack', 'defense', 'tie'], true)) continue;

            foreach ([['attacker', 'offense', 'cross', 'attack'], ['defender', 'defense', 'heading', 'defense']] as [$actor, $side, $action, $success]) {
                $id = $event[$actor]['id'] ?? null;
                if ($id === null || isset($stats['players'][$id][$side][$action])) continue;
                $recovered[$id][$side][$action] ??= ['attempts' => 0, 'success' => 0];
                $recovered[$id][$side][$action]['attempts']++;
                if ($winner === $success) $recovered[$id][$side][$action]['success']++;
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
};

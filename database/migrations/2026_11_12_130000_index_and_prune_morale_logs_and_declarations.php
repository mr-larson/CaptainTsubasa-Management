<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Logs de moral et déclarations : index complet (save, joueur, saison, semaine)
 * à la place de l'index (save, joueur), et purge des saisons antérieures à la
 * précédente (SeasonService::pruneOldLogs le fait désormais à chaque intersaison).
 */
return new class extends Migration
{
    private const TABLES = [
        'game_player_morale_logs' => 'game_player_morale_logs_save_player_season_week_index',
        'game_declarations'       => 'game_declarations_save_player_season_week_index',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $index) {
            DB::statement("DELETE t FROM `{$table}` t JOIN `game_saves` s ON s.id = t.game_save_id WHERE t.season < s.season - 1");

            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->index(['game_save_id', 'game_player_id', 'season', 'week'], $index);
            });
            // L'ancien index (save, joueur) est un préfixe du nouveau : redondant.
            // MySQL en a besoin pour la clé étrangère tant que le nouveau n'existe pas.
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex(['game_save_id', 'game_player_id']));
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table => $index) {
            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->index(['game_save_id', 'game_player_id']);
                $blueprint->dropIndex($index);
            });
        }
    }
};

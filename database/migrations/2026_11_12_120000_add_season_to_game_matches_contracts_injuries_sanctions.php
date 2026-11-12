<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rattache matchs, contrats, blessures et sanctions à une saison, pour garder
 * l'historique au lieu de tout supprimer à l'intersaison. Les lignes existantes
 * appartiennent forcément à la saison courante de leur sauvegarde (les saisons
 * précédentes avaient été supprimées).
 */
return new class extends Migration
{
    private const TABLES = ['game_matches', 'game_contracts', 'game_injuries', 'game_sanctions'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unsignedSmallInteger('season')->default(1)->after('game_save_id');
                $blueprint->index(['game_save_id', 'season'], "{$table}_save_season_index");
            });

            DB::statement("UPDATE `{$table}` t JOIN `game_saves` s ON s.id = t.game_save_id SET t.season = COALESCE(s.season, 1)");
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropIndex("{$table}_save_season_index");
                $blueprint->dropColumn('season');
            });
        }
    }
};

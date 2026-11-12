<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Règles d'intégrité jusqu'ici portées par le seul code :
 *  - un joueur / une équipe de référence n'apparaît qu'une fois par sauvegarde ;
 *  - un joueur n'a pas deux contrats qui démarrent la même semaine d'une saison ;
 *  - une équipe n'a qu'un capitaine par saison (colonne générée `captain_slot`,
 *    NULL hors capitaine : les NULL ne se heurtent pas dans un index unique).
 * Le numéro de maillot n'est pas couvert : il est sur le joueur, l'équipe sur son
 * contrat, aucune contrainte unique ne peut les relier.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->assertNoDuplicates('game_players', 'game_save_id, base_player_id', 'base_player_id IS NOT NULL');
        $this->assertNoDuplicates('game_teams', 'game_save_id, base_team_id', 'base_team_id IS NOT NULL');
        $this->assertNoDuplicates('game_contracts', 'game_player_id, season, start_week');

        // Plusieurs capitaines : on garde le contrat le plus récent par équipe et saison.
        DB::statement('UPDATE game_contracts c JOIN (
                SELECT game_team_id, season, MAX(id) AS keep_id FROM game_contracts WHERE is_captain = 1 GROUP BY game_team_id, season HAVING COUNT(*) > 1
            ) d ON d.game_team_id = c.game_team_id AND d.season = c.season
            SET c.is_captain = 0 WHERE c.is_captain = 1 AND c.id <> d.keep_id');

        Schema::table('game_players', function (Blueprint $table) {
            $table->unique(['game_save_id', 'base_player_id'], 'game_players_save_base_player_unique');
        });

        Schema::table('game_teams', function (Blueprint $table) {
            $table->unique(['game_save_id', 'base_team_id'], 'game_teams_save_base_team_unique');
        });

        Schema::table('game_contracts', function (Blueprint $table) {
            $table->tinyInteger('captain_slot')->nullable()->virtualAs('CASE WHEN is_captain = 1 THEN 1 ELSE NULL END');
            $table->unique(['game_team_id', 'season', 'captain_slot'], 'game_contracts_one_captain_per_team_season');
            $table->unique(['game_player_id', 'season', 'start_week'], 'game_contracts_player_season_start_unique');
        });
    }

    public function down(): void
    {
        Schema::table('game_contracts', function (Blueprint $table) {
            $table->dropUnique('game_contracts_player_season_start_unique');
            $table->dropUnique('game_contracts_one_captain_per_team_season');
        });
        Schema::table('game_contracts', fn (Blueprint $table) => $table->dropColumn('captain_slot'));

        Schema::table('game_teams', fn (Blueprint $table) => $table->dropUnique('game_teams_save_base_team_unique'));

        Schema::table('game_players', fn (Blueprint $table) => $table->dropUnique('game_players_save_base_player_unique'));
    }

    /** Arrête la migration AVANT toute modification si des doublons existent. */
    private function assertNoDuplicates(string $table, string $columns, string $where = '1=1'): void
    {
        $duplicates = DB::select("SELECT {$columns}, COUNT(*) AS c FROM {$table} WHERE {$where} GROUP BY {$columns} HAVING c > 1 LIMIT 5");
        if ($duplicates) {
            throw new RuntimeException("Doublons dans {$table} ({$columns}) : corriger avant d'ajouter la contrainte unique. Exemple : " . json_encode($duplicates[0]));
        }
    }
};

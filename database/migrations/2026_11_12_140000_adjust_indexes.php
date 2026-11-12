<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index alignés sur les requêtes réelles : ajout de ceux qui manquaient pour les
 * lectures par save / semaine / équipe, retrait de ceux qui ne servent à rien.
 * Les filtres de saison courante (CurrentSeasonScope) passent par `season`,
 * d'où sa place dans les index des tables de jeu.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Matchs : save + saison + semaine + statut (remplace save + saison, et round seul).
        Schema::table('game_matches', function (Blueprint $table) {
            $table->index(['game_save_id', 'season', 'week', 'status'], 'game_matches_save_season_week_status_index');
        });
        Schema::table('game_matches', function (Blueprint $table) {
            $table->dropIndex('game_matches_save_season_index');
            $table->dropIndex(['round']);
        });

        Schema::table('game_contracts', function (Blueprint $table) {
            $table->index(['game_save_id', 'game_team_id', 'season'], 'game_contracts_save_team_season_index');
        });

        Schema::table('game_bonus_cards', function (Blueprint $table) {
            $table->index(['game_save_id', 'game_team_id', 'status'], 'game_bonus_cards_save_team_status_index');
        });

        // Doublon de l'unique sur `name`, jamais utilisé.
        Schema::table('teams', fn (Blueprint $table) => $table->dropIndex(['name', 'budget', 'wins', 'draws', 'losses']));
        Schema::table('players', fn (Blueprint $table) => $table->dropIndex(['firstname', 'lastname', 'position']));

        // L'index à quatre colonnes servait de support à la clé étrangère team_id :
        // on lui substitue un index simple avant de le retirer.
        Schema::table('contracts', fn (Blueprint $table) => $table->index('team_id', 'contracts_team_id_index'));
        Schema::table('contracts', fn (Blueprint $table) => $table->dropIndex(['team_id', 'player_id', 'start_date', 'end_date']));
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->index(['team_id', 'player_id', 'start_date', 'end_date']);
        });
        Schema::table('contracts', fn (Blueprint $table) => $table->dropIndex('contracts_team_id_index'));

        Schema::table('players', fn (Blueprint $table) => $table->index(['firstname', 'lastname', 'position']));
        Schema::table('teams', fn (Blueprint $table) => $table->index(['name', 'budget', 'wins', 'draws', 'losses']));

        Schema::table('game_bonus_cards', fn (Blueprint $table) => $table->dropIndex('game_bonus_cards_save_team_status_index'));
        Schema::table('game_contracts', fn (Blueprint $table) => $table->dropIndex('game_contracts_save_team_season_index'));

        Schema::table('game_matches', function (Blueprint $table) {
            $table->index(['game_save_id', 'season'], 'game_matches_save_season_index');
            $table->index('round');
        });
        Schema::table('game_matches', fn (Blueprint $table) => $table->dropIndex('game_matches_save_season_week_status_index'));
    }
};

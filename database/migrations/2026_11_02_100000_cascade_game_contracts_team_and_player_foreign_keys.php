<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les FK game_contracts → game_teams / game_players étaient en RESTRICT :
 * supprimer une équipe de partie, ou un joueur dont un ancien contrat (expiré)
 * existe encore, levait une erreur SQL 1451 (→ 500). Un contrat n'a pas de
 * sens sans son équipe ni son joueur : il suit leur suppression, comme le
 * font déjà les matchs (game_matches → game_teams en cascade).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_contracts', function (Blueprint $table) {
            $table->dropForeign(['game_team_id']);
            $table->dropForeign(['game_player_id']);

            $table->foreign('game_team_id')->references('id')->on('game_teams')->cascadeOnDelete();
            $table->foreign('game_player_id')->references('id')->on('game_players')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_contracts', function (Blueprint $table) {
            $table->dropForeign(['game_team_id']);
            $table->dropForeign(['game_player_id']);

            $table->foreign('game_team_id')->references('id')->on('game_teams');
            $table->foreign('game_player_id')->references('id')->on('game_players');
        });
    }
};

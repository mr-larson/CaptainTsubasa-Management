<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compteur de recrues signées pendant la saison (défi « Recruter X joueurs »).
 * Incrémenté à la signature : une recrue libérée ou transférée ensuite compte
 * toujours. Remis à 0 au tirage des défis de la saison.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_teams', function (Blueprint $table) {
            $table->unsignedInteger('season_signings')->default(0)->after('goals_against');
        });
    }

    public function down(): void
    {
        Schema::table('game_teams', function (Blueprint $table) {
            $table->dropColumn('season_signings');
        });
    }
};

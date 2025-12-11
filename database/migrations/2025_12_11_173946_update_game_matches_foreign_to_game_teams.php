<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_matches', function (Blueprint $table) {
            // On vire les anciennes FKs vers teams
            $table->dropForeign(['home_team_id']);
            $table->dropForeign(['away_team_id']);

            // On recrée les FKs vers game_teams
            $table->foreign('home_team_id')
                ->references('id')
                ->on('game_teams')
                ->onDelete('cascade');

            $table->foreign('away_team_id')
                ->references('id')
                ->on('game_teams')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('game_matches', function (Blueprint $table) {
            $table->dropForeign(['home_team_id']);
            $table->dropForeign(['away_team_id']);

            $table->foreign('home_team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('cascade');

            $table->foreign('away_team_id')
                ->references('id')
                ->on('teams')
                ->onDelete('cascade');
        });
    }
};


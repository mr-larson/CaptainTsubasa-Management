<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_teams', function (Blueprint $table) {
            $table->integer('goals_for')->default(0)->after('losses');
            $table->integer('goals_against')->default(0)->after('goals_for');
        });

        // Backfill : reconstruire les buts depuis les matchs déjà joués
        // pour que les saves existantes gardent un classement correct.
        $matches = DB::table('game_matches')
            ->where('status', 'played')
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->get(['home_team_id', 'away_team_id', 'home_score', 'away_score']);

        $for     = [];
        $against = [];
        foreach ($matches as $m) {
            $for[$m->home_team_id]     = ($for[$m->home_team_id]     ?? 0) + $m->home_score;
            $against[$m->home_team_id] = ($against[$m->home_team_id] ?? 0) + $m->away_score;
            $for[$m->away_team_id]     = ($for[$m->away_team_id]     ?? 0) + $m->away_score;
            $against[$m->away_team_id] = ($against[$m->away_team_id] ?? 0) + $m->home_score;
        }

        foreach ($for as $teamId => $goals) {
            DB::table('game_teams')->where('id', $teamId)->update([
                'goals_for'     => $goals,
                'goals_against' => $against[$teamId] ?? 0,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('game_teams', function (Blueprint $table) {
            $table->dropColumn(['goals_for', 'goals_against']);
        });
    }
};

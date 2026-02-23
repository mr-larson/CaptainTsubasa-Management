<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_matches', function (Blueprint $table) {
            /**
             * Stats détaillées du match
             * Structure attendue :
             * {
             *   players: { [game_player_id]: { counters... } },
             *   teams: {
             *     home: { counters... },
             *     away: { counters... }
             *   }
             * }
             */
            $table->json('match_stats')->nullable()->after('away_score');
        });
    }

    public function down(): void
    {
        Schema::table('game_matches', function (Blueprint $table) {
            $table->dropColumn('match_stats');
        });
    }
};

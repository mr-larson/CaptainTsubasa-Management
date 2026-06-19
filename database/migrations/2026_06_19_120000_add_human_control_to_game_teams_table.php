<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_teams', function (Blueprint $table) {
            // Équipe pilotée par un humain (hot-seat multi-manager).
            $table->boolean('is_controlled')->default(false)->after('base_team_id');
            // Ordre de passage du joueur dans le tour (1 = premier).
            $table->unsignedInteger('human_seat')->nullable()->after('is_controlled');
        });

        // Backfill : chaque sauvegarde existante a une unique équipe contrôlée.
        $controlledIds = DB::table('game_saves')
            ->whereNotNull('controlled_game_team_id')
            ->pluck('controlled_game_team_id')
            ->all();

        if (!empty($controlledIds)) {
            DB::table('game_teams')
                ->whereIn('id', $controlledIds)
                ->update(['is_controlled' => true, 'human_seat' => 1]);
        }
    }

    public function down(): void
    {
        Schema::table('game_teams', function (Blueprint $table) {
            $table->dropColumn(['is_controlled', 'human_seat']);
        });
    }
};

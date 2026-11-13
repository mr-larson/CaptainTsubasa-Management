<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à `players` et `game_players` la colonne JSON nullable `secondary_positions`, listant les postes secondaires que le joueur sait occuper.
 */
return new class extends Migration
{
    /** Ajoute les postes secondaires. */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->json('secondary_positions')->nullable()->after('position');
        });

        Schema::table('game_players', function (Blueprint $table) {
            $table->json('secondary_positions')->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('secondary_positions');
        });

        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn('secondary_positions');
        });
    }
};

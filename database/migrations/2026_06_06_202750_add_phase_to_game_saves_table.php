<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à `game_saves` la colonne `phase` (season par défaut) qui indique la phase de jeu en cours dans la sauvegarde.
 */
return new class extends Migration
{
    /** Ajoute la phase de jeu. */
    public function up(): void
    {
        Schema::table('game_saves', function (Blueprint $table) {
            $table->string('phase', 32)->default('season')->after('week');
        });
    }

    public function down(): void
    {
        Schema::table('game_saves', function (Blueprint $table) {
            $table->dropColumn('phase');
        });
    }
};

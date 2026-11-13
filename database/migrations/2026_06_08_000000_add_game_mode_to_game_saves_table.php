<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à `game_saves` la colonne `game_mode` (prebuilt par défaut) qui distingue les modes de création/jeu d'une sauvegarde.
 */
return new class extends Migration
{
    /** Ajoute le mode de jeu. */
    public function up(): void
    {
        Schema::table('game_saves', function (Blueprint $table) {
            $table->string('game_mode', 32)->default('prebuilt')->after('phase');
        });
    }

    public function down(): void
    {
        Schema::table('game_saves', function (Blueprint $table) {
            $table->dropColumn('game_mode');
        });
    }
};

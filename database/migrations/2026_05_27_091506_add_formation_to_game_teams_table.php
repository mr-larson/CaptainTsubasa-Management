<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à `game_teams` la colonne `formation` (4-2-2-2 par défaut) qui mémorise le schéma tactique de l'équipe.
 */
return new class extends Migration
{
    /** Ajoute la formation tactique. */
    public function up(): void
    {
        Schema::table('game_teams', function (Blueprint $table) {
            $table->string('formation')->default('4-2-2-2')->after('budget');
        });
    }

    public function down(): void
    {
        Schema::table('game_teams', function (Blueprint $table) {
            $table->dropColumn('formation');
        });
    }
};

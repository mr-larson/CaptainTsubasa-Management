<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Format de compétition de la partie, orthogonal à `game_mode` (qui décrit
     * la construction d'effectif prebuilt/draft) :
     *   - college_league : ligue collège classique (round-robin) — défaut
     *   - world_cup      : tournoi de sélections nationales (poules + bracket)
     */
    public function up(): void
    {
        Schema::table('game_saves', function (Blueprint $table) {
            $table->string('competition_type', 32)->default('college_league')->after('game_mode');
        });
    }

    public function down(): void
    {
        Schema::table('game_saves', function (Blueprint $table) {
            $table->dropColumn('competition_type');
        });
    }
};

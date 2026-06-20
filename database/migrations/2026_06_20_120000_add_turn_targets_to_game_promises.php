<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_promises', function (Blueprint $table) {
            // Promesse "temps de jeu" : minimum de tours à jouer au prochain match
            // (distincte de "titularisation" qui reste basée sur des matchs).
            $table->unsignedSmallInteger('target_turns')->nullable()->after('played_matches');
            $table->unsignedSmallInteger('played_turns')->nullable()->after('target_turns')
                ->comment('Tours réellement joués au match d\'évaluation');
        });
    }

    public function down(): void
    {
        Schema::table('game_promises', function (Blueprint $table) {
            $table->dropColumn(['target_turns', 'played_turns']);
        });
    }
};

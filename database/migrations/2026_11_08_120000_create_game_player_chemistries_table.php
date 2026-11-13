<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table `game_player_chemistries` : alchimie entre deux joueurs d'une sauvegarde (score, matchs partagés, dernier essai, confirmation), avec une contrainte d'unicité sur la paire.
 */
return new class extends Migration
{
    /** Crée la table des alchimies. */
    public function up(): void
    {
        Schema::create('game_player_chemistries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_save_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_a_id')->constrained('game_players')->cascadeOnDelete();
            $table->foreignId('player_b_id')->constrained('game_players')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->unsignedInteger('shared_matches')->default(0);
            $table->unsignedInteger('last_attempt_at_match_count')->nullable();
            $table->boolean('confirmed')->default(false);
            $table->unique(['game_save_id', 'player_a_id', 'player_b_id'], 'game_chemistry_unique_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_player_chemistries');
    }
};

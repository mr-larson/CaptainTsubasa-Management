<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table `game_contracts` : contrats propres à une sauvegarde, reliant une équipe de jeu à un joueur de jeu avec un salaire et une durée exprimée en semaines (`end_week` nul = contrat en cours).
 */
return new class extends Migration {
    /** Crée la table des contrats de sauvegarde. */
    public function up(): void
    {
        Schema::create('game_contracts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_save_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('game_team_id')
                ->constrained('game_teams');

            $table->foreignId('game_player_id')
                ->constrained('game_players');

            $table->integer('salary')->default(0);
            $table->integer('start_week')->default(1);
            $table->integer('end_week')->nullable(); // null = en cours

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_contracts');
    }
};

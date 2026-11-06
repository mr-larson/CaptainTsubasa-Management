<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formation d'un joueur à un nouveau poste (ajouté à ses postes secondaires).
 * Le joueur est indisponible pour les matchs des semaines [week_start, week_return[,
 * puis le poste est acquis à la clôture de la semaine week_return - 1.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_position_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_save_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_team_id')->constrained()->cascadeOnDelete();
            $table->string('from_position');
            $table->string('target_position');
            $table->unsignedInteger('week_start');
            $table->unsignedInteger('week_return');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['game_save_id', 'week_return']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_position_trainings');
    }
};

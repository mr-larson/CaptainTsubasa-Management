<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            // Relation avec l'entraîneur (le joueur humain) : -100 → +100.
            // N'a de sens que pour l'équipe contrôlée ; 0 = neutre ailleurs.
            $table->smallInteger('coach_affinity')->default(0)->after('morale');
        });

        Schema::create('game_promises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_save_id')->constrained('game_saves')->cascadeOnDelete();
            $table->foreignId('game_player_id')->constrained('game_players')->cascadeOnDelete();
            $table->foreignId('game_team_id')->constrained('game_teams')->cascadeOnDelete();
            $table->string('type')->default('playing_time');
            $table->unsignedSmallInteger('start_week');
            $table->unsignedSmallInteger('due_week');
            $table->unsignedTinyInteger('target_matches');
            $table->unsignedTinyInteger('played_matches')->nullable()->comment('Renseigné à l\'évaluation');
            $table->string('status')->default('pending')->comment('pending | kept | broken');
            $table->unsignedSmallInteger('season')->default(1);
            $table->timestamps();

            $table->index(['game_save_id', 'game_player_id']);
            $table->index(['game_save_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_promises');

        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn('coach_affinity');
        });
    }
};

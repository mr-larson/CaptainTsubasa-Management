<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Demandes formulées par les joueurs (congé, temps de jeu, souci physique),
        // auxquelles l'entraîneur répond.
        Schema::create('game_player_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_save_id')->constrained('game_saves')->cascadeOnDelete();
            $table->foreignId('game_team_id')->constrained('game_teams')->cascadeOnDelete();
            $table->foreignId('game_player_id')->constrained('game_players')->cascadeOnDelete();
            $table->string('type')->comment('leave | playing_time | ailment');
            $table->string('status')->default('pending')->comment('pending | accepted | declined | ignored');
            $table->string('choice')->nullable()->comment('Souci physique : rest | physio | play');
            $table->string('description')->nullable()->comment('Libellé du souci physique');
            $table->unsignedInteger('cost')->default(0)->comment('Coût du kiné proposé');
            $table->unsignedSmallInteger('week');
            $table->unsignedSmallInteger('season')->default(1);
            $table->foreignId('game_promise_id')->nullable()->constrained('game_promises')->nullOnDelete();
            $table->timestamps();

            $table->index(['game_save_id', 'game_team_id', 'status']);
            $table->index(['game_save_id', 'game_player_id']);
        });

        Schema::table('game_promises', function (Blueprint $table) {
            // coach = promesse spontanée ; player = demande acceptée ; ailment = repos
            // accordé pour un souci physique. Une promesse issue d'une demande coûte
            // plus cher si elle est rompue.
            $table->string('origin')->default('coach')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('game_promises', function (Blueprint $table) {
            $table->dropColumn('origin');
        });

        Schema::dropIfExists('game_player_requests');
    }
};

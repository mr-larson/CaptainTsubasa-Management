<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('soccer_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_a_id');  // ID de l'équipe A
            $table->unsignedBigInteger('team_b_id');  // ID de l'équipe B
            $table->integer('score_team_a')->nullable();  // Score de l'équipe A
            $table->integer('score_team_b')->nullable();  // Score de l'équipe B
            $table->json('match_statistics')->nullable();  // Statistiques du match
            $table->string('weather')->nullable();  // Météo
            $table->json('red_cards')->nullable();  // Cartons rouges (liste d'IDs de joueurs)
            $table->json('yellow_cards')->nullable();  // Cartons jaunes (liste d'IDs de joueurs)
            $table->json('team_a_players')->nullable();  // Liste d'IDs de joueurs de l'équipe A
            $table->json('team_b_players')->nullable();  // Liste d'IDs de joueurs de l'équipe B
            $table->date('match_date');  // Date du match
            $table->text('highlights')->nullable();  // Résumé ou moments forts du match
            $table->foreign('team_a_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('team_b_id')->references('id')->on('teams')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soccer_matches');
    }
};

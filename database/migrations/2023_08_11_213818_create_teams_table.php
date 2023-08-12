<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamsTable extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();  // Nom
            $table->string('logo_path')->nullable();  // Chemin du logo
            $table->integer('budget');  // Budget actuel
            $table->integer('points')->default(0);  // Points au classement
            $table->integer('wins')->default(0);  // Victoires
            $table->integer('draws')->default(0);  // Matchs nuls
            $table->integer('losses')->default(0);  // Défaites
            $table->json('team_stats_bonus')->nullable();  // Bonus de stats d'équipe : {"tir": 10%, "passe": 5%, ...}
            $table->json('active_cards')->nullable(); // Cartes bonus/malus actives : [{"card_id": 1, "expiry_date": "2023-09-12"}, ...]
            $table->timestamps();
            $table->softDeletes();
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayersTable extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name');  // Nom
            $table->string('first_name');  // Prénom
            $table->string('image_path')->nullable();  // Chemin de l'image
            $table->string('nationality');  // Nationalité
            $table->date('birth_date');  // Date de naissance
            $table->integer('height');  // Taille
            $table->integer('weight');  // Poids
            $table->enum('period', ['collège', 'lycée', 'pro']);  // Période
            $table->unsignedBigInteger('current_team_id')->nullable();  // ID de l'équipe actuelle
            $table->json('stats'); // Statistiques : {"tir": 10, "passe": 5, ...}
            $table->json('positions'); // Postes : ["attaquant", "milieu"]
            $table->json('special_skills'); // Compétences spéciales : ["dribble acrobatique", "tacle extraordinaire"]
            $table->json('special_moves'); // Coups spéciaux : [{"type": "offensif", "name": "Tir spécial", "endurance": 10}, ...]
            $table->json('weather_bonus'); // Bonus météo : {"pluie": 1.5, "soleil": 0.8, ...}
            $table->integer('cost');  // Coût
            $table->integer('current_contract_duration')->nullable();  // Durée de contrat en cours
            $table->integer('fatigue')->default(0);  // Fatigue
            $table->float('injury_risk', 3, 2)->default(0.0);  // Risque de blessure (stocké comme pourcentage, ex: 0.10 pour 10%)
            $table->boolean('is_injured')->default(false);  // Est blessé
            $table->foreign('current_team_id')->references('id')->on('teams')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });        
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
}

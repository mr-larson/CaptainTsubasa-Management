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
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->json('player_ids'); // Liste d'IDs de joueurs concernés
            $table->foreignId('training_type_id')->constrained('training_types'); // Référence vers la table de types d'entraînements
            $table->json('stat_increase'); // Par exemple: {"tir": 1, "passe": 2}
            $table->integer('fatigue_generated');
            $table->enum('training_mode', ['individual', 'group'])->default('individual'); // Mode d'entraînement : individuel ou groupe
            $table->timestamps();
            $table->softDeletes();
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};

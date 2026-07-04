<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Liens d'alchimie entre joueurs du catalogue (duos issus d'une même œuvre :
     * Golden Combi, jumeaux Tachibana…). Un lien débloque l'action « une-deux »
     * en match quand les deux joueurs sont sur le terrain dans la même équipe.
     * Données statiques de lore : pas de copie par sauvegarde, les game_players
     * les résolvent via base_player_id.
     */
    public function up(): void
    {
        Schema::create('player_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_a_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('player_b_id')->constrained('players')->cascadeOnDelete();
            $table->string('label')->nullable(); // nom du duo, ex. « Golden Combi »
            $table->timestamps();

            $table->unique(['player_a_id', 'player_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_links');
    }
};

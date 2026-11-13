<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table `contracts` du catalogue de référence, qui lie une équipe à un joueur avec un salaire et des dates de début/fin (clés étrangères en cascade, suppression logique, index de recherche).
 */
class CreateContractsTable extends Migration
{
    /** Crée la table des contrats de référence. */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('player_id');
            $table->unsignedInteger('salary');
            $table->date('start_date');
            $table->date('end_date');

            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'player_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
}


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');  // ID de l'équipe
            $table->unsignedBigInteger('player_id');  // ID du joueur
            $table->date('start_date');  // Date de début du contrat
            $table->date('end_date');  // Date de fin du contrat
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        }); 
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
}


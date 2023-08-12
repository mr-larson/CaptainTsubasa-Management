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
        Schema::create('sanctions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');  // ID du joueur sanctionné
            $table->unsignedBigInteger('match_id');  // ID du match où la sanction a été infligée
            $table->enum('type', ['yellow', 'red', 'double_yellow']);  // Type de carton (jaune, rouge, ou deux jaunes)
            $table->integer('duration');  // Durée de la sanction en matchs
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('match_id')->references('id')->on('soccer_matches')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanctions');
    }
};

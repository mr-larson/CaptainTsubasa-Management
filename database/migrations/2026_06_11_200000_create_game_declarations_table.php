<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_save_id')->constrained('game_saves')->cascadeOnDelete();
            $table->foreignId('game_player_id')->constrained('game_players')->cascadeOnDelete();
            $table->string('type')->comment('praise | criticize');
            $table->boolean('deserved')->default(true)->comment('Le joueur était-il en forme / méforme ?');
            $table->string('outcome')->nullable()->comment('well_received | backfired | proud_reaction');
            $table->smallInteger('affinity_delta')->default(0);
            $table->smallInteger('morale_delta')->default(0);
            $table->unsignedSmallInteger('week');
            $table->unsignedSmallInteger('season')->default(1);
            $table->timestamps();

            $table->index(['game_save_id', 'game_player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_declarations');
    }
};

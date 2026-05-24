<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_injuries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_save_id')->constrained('game_saves')->cascadeOnDelete();
            $table->foreignId('game_player_id')->constrained('game_players')->cascadeOnDelete();
            $table->foreignId('game_match_id')->nullable()->constrained('game_matches')->nullOnDelete();
            $table->enum('severity', ['light', 'moderate', 'severe'])->default('light');
            // light = 1 semaine, moderate = 2-3 semaines, severe = 4-6 semaines
            $table->unsignedTinyInteger('weeks_out')->default(1);
            $table->unsignedSmallInteger('week_injured')->comment('Semaine du match où la blessure s\'est produite');
            $table->unsignedSmallInteger('week_return')->comment('Semaine de retour (week_injured + weeks_out)');
            $table->string('description')->nullable()->comment('Ex: Cheville, Genou...');
            $table->timestamps();

            $table->index(['game_save_id', 'game_player_id']);
            $table->index(['game_save_id', 'week_return']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_injuries');
    }
};

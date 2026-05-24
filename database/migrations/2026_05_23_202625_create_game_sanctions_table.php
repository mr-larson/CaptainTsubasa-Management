<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_save_id')->constrained('game_saves')->cascadeOnDelete();
            $table->foreignId('game_player_id')->constrained('game_players')->cascadeOnDelete();
            $table->foreignId('game_match_id')->nullable()->constrained('game_matches')->nullOnDelete();
            $table->enum('type', ['yellow', 'red', 'double_yellow'])
                ->comment('yellow=carton jaune, double_yellow=2e jaune=rouge, red=carton rouge direct');
            $table->unsignedTinyInteger('weeks_suspended')->default(1)
                ->comment('yellow=0 (sauf cumul), double_yellow=1, red=2-3');
            $table->unsignedSmallInteger('week_match')->comment('Semaine du match');
            $table->unsignedSmallInteger('week_return')->comment('Semaine de retour');
            $table->unsignedTinyInteger('yellow_card_count')->default(0)
                ->comment('Cumul de cartons jaunes cette saison pour ce joueur');
            $table->timestamps();

            $table->index(['game_save_id', 'game_player_id']);
            $table->index(['game_save_id', 'week_return']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sanctions');
    }
};

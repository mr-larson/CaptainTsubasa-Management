<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_contracts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_save_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('game_team_id')
                ->constrained('game_teams');

            $table->foreignId('game_player_id')
                ->constrained('game_players');

            $table->integer('salary')->default(0);
            $table->integer('start_week')->default(1);
            $table->integer('end_week')->nullable(); // null = en cours

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_contracts');
    }
};

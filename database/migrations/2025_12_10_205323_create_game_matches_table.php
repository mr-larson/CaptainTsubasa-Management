<?php
// database/migrations/2025_12_10_000000_create_game_matches_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_save_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('week')->default(1);

            $table->foreignId('home_team_id')
                ->constrained('teams')
                ->cascadeOnDelete();

            $table->foreignId('away_team_id')
                ->constrained('teams')
                ->cascadeOnDelete();

            // statut & résultat
            $table->enum('status', ['scheduled', 'played', 'cancelled'])->default('scheduled');
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_matches');
    }
};

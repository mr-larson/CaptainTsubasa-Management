<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_bonus_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_save_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bonus_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_team_id')->constrained()->cascadeOnDelete();
            $table->enum('tier', ['bronze', 'silver', 'gold']);
            $table->unsignedInteger('cost_paid');
            $table->enum('status', ['available', 'used'])->default('available');
            // Joueur cible pour les cartes de type "player" — assigné à l'activation
            $table->foreignId('target_player_id')->nullable()->constrained('game_players')->nullOnDelete();
            $table->unsignedInteger('purchased_season');
            $table->unsignedInteger('purchased_week');
            $table->unsignedInteger('used_season')->nullable();
            $table->unsignedInteger('used_week')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_bonus_cards');
    }
};

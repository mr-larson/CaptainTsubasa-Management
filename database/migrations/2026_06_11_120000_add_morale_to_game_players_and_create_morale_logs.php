<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            // 0-20 révolté, 21-40 mécontent, 41-60 neutre, 61-80 satisfait, 81-100 très satisfait
            $table->unsignedTinyInteger('morale')->default(60)->after('cost');
        });

        Schema::create('game_player_morale_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_save_id')->constrained('game_saves')->cascadeOnDelete();
            $table->foreignId('game_player_id')->constrained('game_players')->cascadeOnDelete();
            $table->string('source')->comment('result | playing_time | salary');
            $table->smallInteger('value')->comment('Variation de moral appliquée (signée)');
            $table->string('label')->nullable()->comment('Ex: Victoire contre Toho');
            $table->unsignedSmallInteger('week');
            $table->unsignedSmallInteger('season')->default(1);
            $table->timestamps();

            $table->index(['game_save_id', 'game_player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_player_morale_logs');

        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn('morale');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->enum('tier', ['bronze', 'silver', 'gold']);
            $table->enum('target', ['self', 'player', 'match', 'finance']);
            $table->enum('execution_phase', ['immediate', 'pre_match', 'post_match', 'weekly_reset']);
            $table->string('effect_type');          // stamina_boost, stat_boost, injury_reduce, revenue_boost
            $table->json('effect_value');            // {"amount": 20} ou {"stat": "attack", "amount": 10}
            $table->unsignedInteger('cost');         // coût en euros
            $table->unsignedInteger('base_weight'); // poids de base pour le tirage (100 = neutre)
            $table->string('icon')->default('🃏');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_cards');
    }
};

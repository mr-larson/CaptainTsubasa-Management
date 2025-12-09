<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_saves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();

            // collège / lycée / pro (texte court)
            $table->string('period')->default('college');

            // progression simple
            $table->unsignedInteger('season')->default(1);
            $table->unsignedInteger('week')->default(1);

            // nom visible par le joueur (optionnel)
            $table->string('label')->nullable();

            // état JSON extensible (budget, contrats, fatigue...)
            $table->json('state')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_saves');
    }
};

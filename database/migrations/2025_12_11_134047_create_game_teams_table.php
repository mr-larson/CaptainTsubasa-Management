<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_teams', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_save_id')
                ->constrained()
                ->cascadeOnDelete();

            // lien vers l’équipe de base (Team)
            $table->foreignId('base_team_id')
                ->nullable()
                ->constrained('teams');

            $table->string('name');
            $table->text('description')->nullable();

            $table->integer('budget')->default(0);

            $table->integer('wins')->default(0);
            $table->integer('draws')->default(0);
            $table->integer('losses')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_teams');
    }
};

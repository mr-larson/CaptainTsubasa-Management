<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_players', function (Blueprint $table) {
            $table->id();

            $table->foreignId('game_save_id')
                ->constrained()
                ->cascadeOnDelete();

            // joueur de base (Player)
            $table->foreignId('base_player_id')
                ->nullable()
                ->constrained('players');

            $table->string('firstname');
            $table->string('lastname');
            $table->string('position');

            // core
            $table->unsignedTinyInteger('speed')->default(50);
            $table->unsignedTinyInteger('stamina')->default(50);
            $table->unsignedTinyInteger('attack')->default(50);
            $table->unsignedTinyInteger('defense')->default(50);

            // offensif
            $table->unsignedTinyInteger('shot')->default(50);
            $table->unsignedTinyInteger('pass')->default(50);
            $table->unsignedTinyInteger('dribble')->default(50);

            // défensif
            $table->unsignedTinyInteger('block')->default(50);
            $table->unsignedTinyInteger('intercept')->default(50);
            $table->unsignedTinyInteger('tackle')->default(50);

            // gardien
            $table->unsignedTinyInteger('hand_save')->default(0);
            $table->unsignedTinyInteger('punch_save')->default(0);

            $table->integer('cost')->default(0); // coût / match ou salaire base

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_players');
    }
};

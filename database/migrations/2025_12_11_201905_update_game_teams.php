<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_saves', function (Blueprint $table) {
            $table->foreignId('controlled_game_team_id')
                ->nullable()
                ->constrained('game_teams')
                ->nullOnDelete();

            $table->string('control_mode')->default('both'); // both | single
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_saves', function (Blueprint $table) {
            $table->dropForeign(['controlled_game_team_id']);
            $table->dropColumn('control_mode');

        });
    }
};

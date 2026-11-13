<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permet à `game_position_trainings` de porter un entraînement de coup spécial : `target_position` devient nullable, et on ajoute `special_move` (JSON) et `cost`. `down()` supprime d'abord les lignes de coups spéciaux.
 */
return new class extends Migration {
    /** Rend la position nullable et ajoute coup spécial et coût. */
    public function up(): void
    {
        Schema::table('game_position_trainings', function (Blueprint $table) {
            $table->string('target_position')->nullable()->change();
            $table->json('special_move')->nullable();
            $table->unsignedInteger('cost')->nullable();
        });
    }

    public function down(): void
    {
        DB::table('game_position_trainings')->whereNotNull('special_move')->delete();
        Schema::table('game_position_trainings', function (Blueprint $table) {
            $table->dropColumn(['special_move', 'cost']);
            $table->string('target_position')->nullable(false)->change();
        });
    }
};

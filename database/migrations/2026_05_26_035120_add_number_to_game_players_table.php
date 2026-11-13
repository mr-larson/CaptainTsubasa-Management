<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à `game_players` le numéro de maillot `number` (0 par défaut, placé après `lastname`).
 */
return new class extends Migration
{
    /** Ajoute le numéro de maillot. */
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->unsignedTinyInteger('number')->default(0)->after('lastname');
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn('number');
        });
    }
};

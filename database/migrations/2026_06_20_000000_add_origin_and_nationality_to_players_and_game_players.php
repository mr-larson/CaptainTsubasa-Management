<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // origin     : œuvre de provenance du joueur (captain_tsubasa, blue_lock,
        //              hungry_heart, ecole_des_champions, ao_ashi, original…),
        //              utilisée pour filtrer le marché des transferts.
        // nationality: prévu pour un usage futur (laissé vide pour l'instant).
        Schema::table('players', function (Blueprint $table) {
            $table->string('origin', 50)->nullable()->after('position')->index();
            $table->string('nationality', 50)->nullable()->after('origin');
        });

        Schema::table('game_players', function (Blueprint $table) {
            $table->string('origin', 50)->nullable()->after('position')->index();
            $table->string('nationality', 50)->nullable()->after('origin');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['origin', 'nationality']);
        });

        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn(['origin', 'nationality']);
        });
    }
};

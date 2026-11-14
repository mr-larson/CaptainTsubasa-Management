<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à `game_players` la colonne `luck`, puis migre les données : chaque joueur existant reçoit une chance tirée au hasard (`PlayerLuck::roll()`). Le tirage est irréversible : `down()` supprime seulement la colonne.
 */
return new class extends Migration {
    /** Ajoute la colonne puis tire la chance des joueurs existants. */
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->unsignedTinyInteger('luck')->default(50); // chance neutre
        });

        DB::table('game_players')->select('id')->chunkById(250, function ($players) {
            foreach ($players as $player) {
                // Même tirage que PlayerLuck::roll() à la date de la migration : moyenne de deux tirages entre 20 et 80.
                $luck = (int) round((random_int(20, 80) + random_int(20, 80)) / 2);
                DB::table('game_players')->where('id', $player->id)->update(['luck' => $luck]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn('luck');
        });
    }
};

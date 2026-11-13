<?php

use App\Helpers\PlayerLuck;
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
            $table->unsignedTinyInteger('luck')->default(PlayerLuck::NEUTRAL);
        });

        DB::table('game_players')->select('id')->chunkById(250, function ($players) {
            foreach ($players as $player) {
                DB::table('game_players')->where('id', $player->id)->update(['luck' => PlayerLuck::roll()]);
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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à `game_players` une colonne `description` (texte, nullable) pour conserver la description du joueur dans la sauvegarde.
 */
return new class extends Migration {
    /** Ajoute la colonne description. */
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->text('description')->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};

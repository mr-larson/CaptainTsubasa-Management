<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à `game_teams` une colonne `logo_path` (nullable) pour copier dans la sauvegarde le logo de l'équipe de référence.
 */
return new class extends Migration {
    /** Ajoute le chemin du logo. */
    public function up(): void
    {
        Schema::table('game_teams', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('game_teams', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};

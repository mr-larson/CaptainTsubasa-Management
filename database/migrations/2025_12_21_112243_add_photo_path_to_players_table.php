<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à `players` une colonne `photo_path` (nullable) pointant vers l'image du joueur.
 */
return new class extends Migration {
    /** Ajoute le chemin de la photo. */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};

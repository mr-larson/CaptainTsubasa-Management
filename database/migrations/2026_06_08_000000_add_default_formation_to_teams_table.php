<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à `teams` la colonne `default_formation` (3-2-3-2 par défaut), formation utilisée par défaut pour l'équipe de référence.
 */
return new class extends Migration
{
    /** Ajoute la formation par défaut. */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('default_formation')->default('3-2-3-2')->after('management_philosophy');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('default_formation');
        });
    }
};

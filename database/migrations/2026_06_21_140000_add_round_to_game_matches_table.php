<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tour de compétition d'un match, pour le mode Coupe du Monde :
     *   - poules        : group_a, group_b, …
     *   - élimination   : semi, final (et plus tard quarter, etc.)
     * NULL pour les matchs de ligue classique (où seul `week` compte).
     */
    public function up(): void
    {
        Schema::table('game_matches', function (Blueprint $table) {
            $table->string('round', 16)->nullable()->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('game_matches', function (Blueprint $table) {
            $table->dropColumn('round');
        });
    }
};

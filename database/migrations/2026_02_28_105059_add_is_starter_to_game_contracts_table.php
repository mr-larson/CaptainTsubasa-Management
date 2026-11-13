<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à `game_contracts` le booléen `is_starter` (true par défaut) qui indique si le joueur fait partie des titulaires de son équipe.
 */
return new class extends Migration {
    /** Ajoute l'indicateur de titulaire. */
    public function up(): void
    {
        Schema::table('game_contracts', function (Blueprint $table) {
            $table->boolean('is_starter')->default(true)->after('end_week');
        });
    }

    public function down(): void
    {
        Schema::table('game_contracts', function (Blueprint $table) {
            $table->dropColumn('is_starter');
        });
    }
};

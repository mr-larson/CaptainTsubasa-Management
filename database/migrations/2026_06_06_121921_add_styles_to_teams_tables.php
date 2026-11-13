<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute `tactical_style` (balanced par défaut) et `management_philosophy` (collective par défaut) à `teams` et `game_teams`, pour caractériser le style de jeu et la philosophie de gestion de chaque équipe.
 */
return new class extends Migration
{
    /** Ajoute les deux colonnes de style aux deux tables. */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('tactical_style', 32)->default('balanced')->after('description');
            $table->string('management_philosophy', 32)->default('collective')->after('tactical_style');
        });

        Schema::table('game_teams', function (Blueprint $table) {
            $table->string('tactical_style', 32)->default('balanced')->after('description');
            $table->string('management_philosophy', 32)->default('collective')->after('tactical_style');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['tactical_style', 'management_philosophy']);
        });

        Schema::table('game_teams', function (Blueprint $table) {
            $table->dropColumn(['tactical_style', 'management_philosophy']);
        });
    }
};

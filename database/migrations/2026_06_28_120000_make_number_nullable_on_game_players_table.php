<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le code traite `number = null` comme « pas de numéro » (joueur libre) :
 * résiliation de contrat (GameContractController), passage de saison
 * (SeasonService) et lectures avec repli `?? $id`. La colonne était pourtant
 * NOT NULL (default 0), ce qui faisait planter ces écritures en MySQL strict.
 * On aligne le schéma sur l'intention du code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->unsignedTinyInteger('number')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->unsignedTinyInteger('number')->default(0)->change();
        });
    }
};

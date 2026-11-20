<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retire trois légendes mexicaines moins connues, ajoutées puis écartées avant tout déploiement
 * (Jorge Campos, Pável Pardo, Raúl Jiménez) : le pays compte aussi des personnages Captain Tsubasa.
 * Suppression logique, et seulement pour les profils sans portrait. Sans effet sur une base qui ne les a jamais reçus.
 */
return new class extends Migration
{
    /** Archive les trois profils s'ils existent encore. */
    public function up(): void
    {
        foreach ([['Jorge', 'Campos'], ['Pável', 'Pardo'], ['Raúl', 'Jiménez']] as [$firstname, $lastname]) {
            DB::table('players')->where('origin', 'world_legends')->where('nationality', 'Mexique')
                ->where('firstname', $firstname)->where('lastname', $lastname)
                ->whereNull('deleted_at')->whereNull('photo_path')
                ->update(['deleted_at' => now()]);
        }
    }

    public function down(): void
    {
        // Les profils archivés restent archivés : ils peuvent être référencés par une partie.
    }
};

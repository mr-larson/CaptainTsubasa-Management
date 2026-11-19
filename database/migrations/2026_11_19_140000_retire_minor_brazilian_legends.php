<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retire dix légendes brésiliennes moins connues, ajoutées puis écartées avant tout déploiement
 * (Gilmar, Nilton Santos, Aldair, Júnior, Falcão, Gérson, Rivelino, Clodoaldo, Careca, Tostão).
 * Suppression logique, et seulement pour les profils sans portrait ni retouche d'image.
 * Sans effet sur une base qui ne les a jamais reçus.
 */
return new class extends Migration
{
    private const RETIRED = [
        ['', 'Gilmar'], ['Nilton', 'Santos'], ['', 'Aldair'], ['', 'Júnior'], ['', 'Falcão'],
        ['', 'Gérson'], ['', 'Rivelino'], ['', 'Clodoaldo'], ['', 'Careca'], ['', 'Tostão'],
    ];

    /** Archive les dix profils s'ils existent encore. */
    public function up(): void
    {
        foreach (self::RETIRED as [$firstname, $lastname]) {
            DB::table('players')->where('origin', 'world_legends')->where('nationality', 'Brésil')
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

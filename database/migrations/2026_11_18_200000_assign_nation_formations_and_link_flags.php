<?php

use App\Models\Nation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Schéma favori de chaque nation (identité de jeu, cohérente avec son style tactique), puis liaison des
 * drapeaux image déjà présents dans public/storage/nations. L'IA n'utilise le schéma favori que si ses
 * joueurs permettent de le remplir (NationalSquadPicker::formationFits).
 */
return new class extends Migration
{
    private const FORMATIONS = [
        'Brésil' => '4-2-1-3', 'Angleterre' => '3-2-2-3', 'Nigéria' => '3-1-3-3', 'Belgique' => '4-2-1-3',
        "Côte d'Ivoire" => '3-2-2-3', 'Canada' => '4-2-1-3', 'Norvège' => '3-1-3-3',
        'Italie' => '5-2-2-1', 'Chine' => '5-3-1-1', 'Ouzbékistan' => '5-2-1-2', 'Paraguay' => '5-2-2-1',
        'Irlande' => '5-3-1-1', 'Maroc' => '5-2-2-1', 'Slovénie' => '5-3-1-1', 'Costa Rica' => '5-2-1-2',
        'Albanie' => '5-3-1-1', 'Finlande' => '5-2-2-1',
        'Japon' => '4-3-2-1', 'Pays-Bas' => '3-4-2-1', 'Espagne' => '4-3-2-1', 'Mexique' => '4-1-3-2',
        'Thaïlande' => '4-3-2-1', 'Portugal' => '4-3-1-2', 'Croatie' => '4-3-2-1', 'Hongrie' => '3-4-2-1',
        'Pérou' => '4-1-3-2', 'Yougoslavie' => '4-3-1-2',
        'France' => '4-2-2-2', 'Argentine' => '4-2-2-2', 'Uruguay' => '5-2-1-2', 'Arabie saoudite' => '4-2-2-2',
        'Corée du Sud' => '4-2-2-2', 'Ghana' => '4-2-2-2', 'Trinité-et-Tobago' => '5-2-1-2', 'Roumanie' => '4-2-2-2',
        'Ukraine' => '4-2-2-2', 'Bulgarie' => '5-2-1-2', 'Liberia' => '5-2-1-2', 'Pologne' => '4-2-2-2',
        'RD Congo' => '5-2-1-2', 'Guinée' => '5-2-1-2', 'Pays de Galles' => '4-2-2-2',
        'Allemagne' => '4-3-1-2', 'Suède' => '4-3-1-2', 'États-Unis' => '3-2-3-2', 'Australie' => '4-3-1-2',
        'Danemark' => '3-2-3-2', 'République tchèque' => '4-3-1-2', 'Serbie' => '3-2-3-2',
    ];

    public function up(): void
    {
        foreach (self::FORMATIONS as $name => $formation) {
            DB::table('nations')->where('name', $name)->update(['default_formation' => $formation, 'updated_at' => now()]);
        }

        Nation::linkFlagsFromDisk();
    }

    public function down(): void
    {
        DB::table('nations')->update(['default_formation' => null, 'flag_path' => null]);
    }
};

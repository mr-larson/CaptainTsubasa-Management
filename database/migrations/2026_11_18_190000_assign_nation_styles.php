<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Identité de jeu de chaque nation : style tactique et philosophie de management.
 *
 * Ces valeurs pilotent la convocation de l'IA (NationalSquadPicker : stats clés du style,
 * poids de la philosophie) et reviennent sur la GameTeam de la sélection. Les valeurs sont celles de
 * App\Enums\TeamStyle. `default_formation` reste volontairement vide : l'IA choisit alors le schéma le
 * mieux adapté à ses joueurs.
 */
return new class extends Migration
{
    /** Nation => [style tactique, philosophie]. */
    private const STYLES = [
        'Japon' => ['possession', 'collective'], 'Allemagne' => ['balanced', 'collective'],
        'France' => ['counter', 'balanced'], 'Brésil' => ['offensive', 'stars'],
        'Argentine' => ['counter', 'stars'], 'Italie' => ['defensive', 'collective'],
        'Pays-Bas' => ['possession', 'collective'], 'Uruguay' => ['counter', 'collective'],
        'Angleterre' => ['offensive', 'balanced'], 'Suède' => ['balanced', 'collective'],
        'Espagne' => ['possession', 'collective'], 'Mexique' => ['possession', 'balanced'],
        'États-Unis' => ['balanced', 'economist'], 'Arabie saoudite' => ['counter', 'balanced'],
        'Corée du Sud' => ['counter', 'collective'], 'Chine' => ['defensive', 'economist'],
        'Ouzbékistan' => ['defensive', 'economist'], 'Nigéria' => ['offensive', 'stars'],
        'Australie' => ['balanced', 'economist'], 'Ghana' => ['counter', 'stars'],
        'Thaïlande' => ['possession', 'economist'], 'Portugal' => ['possession', 'stars'],
        'Croatie' => ['possession', 'balanced'], 'Belgique' => ['offensive', 'stars'],
        'Trinité-et-Tobago' => ['counter', 'economist'], 'Danemark' => ['balanced', 'collective'],
        'Paraguay' => ['defensive', 'collective'], 'République tchèque' => ['balanced', 'collective'],
        'Irlande' => ['defensive', 'collective'], 'Roumanie' => ['counter', 'stars'],
        'Ukraine' => ['counter', 'balanced'], 'Bulgarie' => ['counter', 'stars'],
        'Liberia' => ['counter', 'economist'], 'Hongrie' => ['possession', 'balanced'],
        'Pologne' => ['counter', 'stars'], 'Maroc' => ['defensive', 'collective'],
        'Slovénie' => ['defensive', 'economist'], 'Costa Rica' => ['defensive', 'economist'],
        "Côte d'Ivoire" => ['offensive', 'stars'], 'Serbie' => ['balanced', 'stars'],
        'RD Congo' => ['counter', 'economist'], 'Canada' => ['offensive', 'balanced'],
        'Norvège' => ['offensive', 'stars'], 'Pérou' => ['possession', 'balanced'],
        'Albanie' => ['defensive', 'economist'], 'Guinée' => ['counter', 'economist'],
        'Finlande' => ['defensive', 'collective'], 'Pays de Galles' => ['counter', 'stars'],
        'Yougoslavie' => ['possession', 'stars'],
    ];

    public function up(): void
    {
        foreach (self::STYLES as $name => [$style, $philosophy]) {
            DB::table('nations')->where('name', $name)->update([
                'tactical_style' => $style, 'management_philosophy' => $philosophy, 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('nations')->update(['tactical_style' => 'balanced', 'management_philosophy' => 'collective']);
    }
};

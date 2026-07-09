<?php

namespace Database\Seeders;

use App\Enums\TeamStyle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // Reset table
        Schema::disableForeignKeyConstraints();
        DB::table('teams')->truncate();
        Schema::enableForeignKeyConstraints();

        // Logos : source de vérité unique = public/storage/teams/ (versionné),
        // nommés d'après le slug de l'équipe. Le seeder ne copie plus rien.
        $storageDisk = Storage::disk('public');
        $storageDir = 'teams';

        // ---------
        // LISTE DES EQUIPES
        // [nom, description, style_tactique, philosophie_gestion, formation_par_defaut]
        // ---------
        $teams = [
            ['Nankatsu',  'Équipe équilibrée et technique, centrée sur le jeu collectif et la créativité offensive.',
                TeamStyle::TACTICAL_BALANCED,  TeamStyle::PHILOSOPHY_BALANCED, '4-1-3-2'],

            ['Toho',      'Équipe agressive, axée sur la puissance offensive et le jeu vertical.',
                TeamStyle::TACTICAL_OFFENSIVE,  TeamStyle::PHILOSOPHY_STARS, '4-2-2-2'],

            ['Hanawa',    'Équipe athlétique spécialisée dans les duels et le jeu aérien.',
                TeamStyle::TACTICAL_OFFENSIVE,    TeamStyle::PHILOSOPHY_ECONOMIST, '3-2-2-3'],

            ['Furano',    'Équipe endurante et disciplinée basée sur la constance.',
                TeamStyle::TACTICAL_POSSESSION,  TeamStyle::PHILOSOPHY_COLLECTIVE, '3-2-3-2'],

            ['Otomo',     'Équipe très organisée, défense dense et jeu regroupé.',
                TeamStyle::TACTICAL_COUNTER, TeamStyle::PHILOSOPHY_STARS, '4-2-2-2'],

            ['Azumaichi', 'Équipe très dure et défensive, impact physique et duels.',
                TeamStyle::TACTICAL_POSSESSION,  TeamStyle::PHILOSOPHY_BALANCED, '3-2-2-3'],

            ['Musashi',   'Équipe technique et tactique privilégiant le jeu posé.',
                TeamStyle::TACTICAL_POSSESSION, TeamStyle::PHILOSOPHY_STARS, '3-1-3-3'],

            ['Shutetsu',  'Équipe solide et bien structurée, axée sur le collectif.',
                TeamStyle::TACTICAL_BALANCED,   TeamStyle::PHILOSOPHY_COLLECTIVE, '4-2-2-2'],

            ['Meiwa',     'Équipe agressive et collective, style pressing-intensité.',
                TeamStyle::TACTICAL_OFFENSIVE,  TeamStyle::PHILOSOPHY_BALANCED, '4-3-1-2'],

            ['Hirado',    'Équipe ultra-physique, défense brutale et jeu direct.',
                TeamStyle::TACTICAL_COUNTER,    TeamStyle::PHILOSOPHY_ECONOMIST, '5-2-2-1'],

            ['Naniwa',    'Équipe défensive, bloc bas compact et gardien solide.',
                TeamStyle::TACTICAL_DEFENSIVE,  TeamStyle::PHILOSOPHY_BALANCED, '4-3-1-2'],

            ['Minawi',    'Équipe simple, équilibrée mais sans vraie star.',
                TeamStyle::TACTICAL_BALANCED,   TeamStyle::PHILOSOPHY_ECONOMIST, '4-2-2-2'],

            ['Nakahara',  'Équipe modeste, jeu structuré mais peu spectaculaire.',
                TeamStyle::TACTICAL_COUNTER,   TeamStyle::PHILOSOPHY_COLLECTIVE, '3-3-2-2'],

            ['Shimizu',   'Équipe prudente, organisation correcte mais limitée.',
                TeamStyle::TACTICAL_DEFENSIVE,  TeamStyle::PHILOSOPHY_ECONOMIST, '5-1-2-2'],

            ['Shimada',   'Équipe neutre, disciplinée, bloc compact.',
                TeamStyle::TACTICAL_DEFENSIVE,  TeamStyle::PHILOSOPHY_COLLECTIVE, '5-3-1-1'],
        ];


        foreach ($teams as [$name, $description, $tacticalStyle, $philosophy, $defaultFormation]) {

            // slug basé sur le nom
            $slug = Str::slug($name);

            $extensions = ['png', 'jpg', 'jpeg', 'webp'];
            $logoPathDb = null;

            foreach ($extensions as $ext) {
                $candidate = $storageDir . '/' . $slug . '.' . $ext;
                if ($storageDisk->exists($candidate)) {
                    $logoPathDb = $candidate;
                    break;
                }
            }

            DB::table('teams')->insert([
                'name'                  => $name,
                'budget'                => 5000,
                'wins'                  => 0,
                'draws'                 => 0,
                'losses'                => 0,
                'description'           => $description,
                'logo_path'             => $logoPathDb,
                'tactical_style'        => $tacticalStyle,
                'management_philosophy' => $philosophy,
                'default_formation'     => $defaultFormation,
            ]);
        }
    }
}

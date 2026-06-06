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

        $imagesSourceDir = database_path('seeders/assets/teams');
        $storageDisk = Storage::disk('public');
        $storageDir = 'teams';

        if (!$storageDisk->exists($storageDir)) {
            $storageDisk->makeDirectory($storageDir);
        }

        // ---------
        // LISTE DES EQUIPES
        // [nom, description, style_tactique, philosophie_gestion]
        // ---------
        $teams = [
            ['Nankatsu',  'Équipe équilibrée et technique, centrée sur le jeu collectif et la créativité offensive.',
                TeamStyle::TACTICAL_BALANCED,  TeamStyle::PHILOSOPHY_BALANCED],

            ['Toho',      'Équipe agressive, axée sur la puissance offensive et le jeu vertical.',
                TeamStyle::TACTICAL_OFFENSIVE,  TeamStyle::PHILOSOPHY_STARS],

            ['Hanawa',    'Équipe athlétique spécialisée dans les duels et le jeu aérien.',
                TeamStyle::TACTICAL_OFFENSIVE,    TeamStyle::PHILOSOPHY_ECONOMIST],

            ['Furano',    'Équipe endurante et disciplinée basée sur la constance.',
                TeamStyle::TACTICAL_POSSESSION,  TeamStyle::PHILOSOPHY_COLLECTIVE],

            ['Otomo',     'Équipe très organisée, défense dense et jeu regroupé.',
                TeamStyle::TACTICAL_COUNTER, TeamStyle::PHILOSOPHY_STARS],

            ['Azumaichi', 'Équipe très dure et défensive, impact physique et duels.',
                TeamStyle::TACTICAL_POSSESSION,  TeamStyle::PHILOSOPHY_BALANCED],

            ['Musashi',   'Équipe technique et tactique privilégiant le jeu posé.',
                TeamStyle::TACTICAL_POSSESSION, TeamStyle::PHILOSOPHY_STARS],

            ['Shutetsu',  'Équipe solide et bien structurée, axée sur le collectif.',
                TeamStyle::TACTICAL_BALANCED,   TeamStyle::PHILOSOPHY_COLLECTIVE],

            ['Meiwa',     'Équipe agressive et collective, style pressing-intensité.',
                TeamStyle::TACTICAL_OFFENSIVE,  TeamStyle::PHILOSOPHY_BALANCED],

            ['Hirado',    'Équipe ultra-physique, défense brutale et jeu direct.',
                TeamStyle::TACTICAL_COUNTER,    TeamStyle::PHILOSOPHY_ECONOMIST],

            ['Naniwa',    'Équipe défensive, bloc bas compact et gardien solide.',
                TeamStyle::TACTICAL_DEFENSIVE,  TeamStyle::PHILOSOPHY_BALANCED],

            ['Minawi',    'Équipe simple, équilibrée mais sans vraie star.',
                TeamStyle::TACTICAL_BALANCED,   TeamStyle::PHILOSOPHY_ECONOMIST],

            ['Nakahara',  'Équipe modeste, jeu structuré mais peu spectaculaire.',
                TeamStyle::TACTICAL_COUNTER,   TeamStyle::PHILOSOPHY_COLLECTIVE],

            ['Shimizu',   'Équipe prudente, organisation correcte mais limitée.',
                TeamStyle::TACTICAL_DEFENSIVE,  TeamStyle::PHILOSOPHY_ECONOMIST],

            ['Shimada',   'Équipe neutre, disciplinée, bloc compact.',
                TeamStyle::TACTICAL_DEFENSIVE,  TeamStyle::PHILOSOPHY_COLLECTIVE],
        ];


        foreach ($teams as [$name, $description, $tacticalStyle, $philosophy]) {

            // slug basé sur le nom
            $slug = Str::slug($name);

            $extensions = ['png', 'jpg', 'jpeg', 'webp'];
            $foundPath = null;

            foreach ($extensions as $ext) {
                $candidate = $imagesSourceDir . DIRECTORY_SEPARATOR . $slug . '.' . $ext;
                if (is_file($candidate)) {
                    $foundPath = $candidate;
                    break;
                }
            }

            $logoPathDb = null;

            if ($foundPath) {
                $filename = $slug . '.' . pathinfo($foundPath, PATHINFO_EXTENSION);
                $destPath = $storageDir . '/' . $filename;

                if (!$storageDisk->exists($destPath)) {
                    $storageDisk->put($destPath, file_get_contents($foundPath));
                }

                $logoPathDb = $destPath;
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
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

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
        // ---------
        $teams = [
            ['Nankatsu',  'Équipe équilibrée et technique, centrée sur le jeu collectif et la créativité offensive.'],
            ['Toho',      'Équipe agressive, axée sur la puissance offensive et le jeu vertical.'],
            ['Hanawa',    'Équipe athlétique spécialisée dans les duels et le jeu aérien.'],
            ['Furano',    'Équipe endurante et disciplinée basée sur la constance.'],
            ['Otomo',     'Équipe très organisée, défense dense et jeu regroupé.'],
            ['Azumaichi', 'Équipe très dure et défensive, impact physique et duels.'],
            ['Musashi',   'Équipe technique et tactique privilégiant le jeu posé.'],
            ['Shutetsu',  'Équipe solide et bien structurée, axée sur le collectif.'],
            ['Meiwa',     'Équipe agressive et collective, style pressing-intensité.'],
            ['Hirado',    'Équipe ultra-physique, défense brutale et jeu direct.'],
            ['Naniwa',    'Équipe défensive, bloc bas compact et gardien solide.'],
            ['Minawi',    'Équipe simple, équilibrée mais sans vraie star.'],
            ['Nakahara',  'Équipe modeste, jeu structuré mais peu spectaculaire.'],
            ['Shimizu',   'Équipe prudente, organisation correcte mais limitée.'],
            ['Shimada',   'Équipe neutre, disciplinée, bloc compact.'],
        ];


        foreach ($teams as [$name, $description]) {

        // slug basé sur le nom => ex: "Nankatsu" → "nankatsu"
        $slug = Str::slug($name);

        // On va chercher un fichier du même nom dans database/seeders/assets/teams
        // (supporte .png, .jpg, .jpeg, .webp)
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
            $destPath = $storageDir . '/' . $filename; // teams/toho.webp

            if (!$storageDisk->exists($destPath)) {
                $storageDisk->put($destPath, file_get_contents($foundPath));
            }

            $logoPathDb = $destPath;
        }

        DB::table('teams')->insert([
            'name'        => $name,
            'budget'      => 10000,
            'wins'        => 0,
            'draws'       => 0,
            'losses'      => 0,
            'description' => $description,
            'logo_path'   => $logoPathDb, // NULL si image absente
        ]);
    }
    }
}

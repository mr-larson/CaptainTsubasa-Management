<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute dix-neuf personnages internationaux de Captain Tsubasa relevés sur le site officiel Dream Team
 * (Thaïlande, Suède, Espagne, Angleterre, Danemark, Allemagne, France, Pays-Bas, Mexique, Brésil), sans portrait
 * pour l'instant. Instantané figé dans `database/data/tsubasa-international-dreamteam-2026-11-20.json`. Un joueur déjà présent n'est pas dupliqué.
 */
return new class extends Migration
{
    /** Insère les joueurs absents du catalogue. */
    public function up(): void
    {
        $players = json_decode(file_get_contents(database_path('data/tsubasa-international-dreamteam-2026-11-20.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($players): void {
            foreach ($players as $row) {
                if (DB::table('players')->where('origin', $row['origin'])->where('nationality', $row['nationality'])
                    ->where('firstname', $row['firstname'])->where('lastname', $row['lastname'])->exists()) {
                    continue;
                }
                DB::table('players')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Les joueurs peuvent être référencés par une partie existante.
    }
};

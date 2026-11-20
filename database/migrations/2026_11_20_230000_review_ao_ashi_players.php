<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Revue d'Ao Ashi : Ashito Aoi devient milieu (postes secondaires défenseur et attaquant, interception 32),
 * Nakamura et Togashi sont légèrement baissés, Motoki perd un peu d'attaque. Instantané figé dans
 * `database/data/ao-ashi-review-2026-11-20.json`. Seuls les joueurs existants sont mis à jour (stats, coût,
 * postes, description) ; leur portrait n'est pas touché et aucun joueur n'est créé.
 */
return new class extends Migration
{
    /** Met à jour les joueurs Ao Ashi concernés. */
    public function up(): void
    {
        $players = json_decode(file_get_contents(database_path('data/ao-ashi-review-2026-11-20.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($players): void {
            foreach ($players as $row) {
                DB::table('players')->where('origin', $row['origin'])
                    ->where('firstname', $row['firstname'])->where('lastname', $row['lastname'])
                    ->update([
                        'stats' => $row['stats'], 'heading' => $row['heading'], 'cost' => $row['cost'],
                        'position' => $row['position'], 'secondary_positions' => $row['secondary_positions'],
                        'description' => $row['description'], 'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        // Les joueurs peuvent être référencés par une partie existante.
    }
};

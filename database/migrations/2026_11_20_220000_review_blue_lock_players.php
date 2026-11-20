<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Revue de Blue Lock : corrige les stats d'Isagi, Baro, Nagi, Reo (désormais milieu) et Gagamaru, et ajoute
 * Sae Itoshi, Noel Noa et Michael Kaiser. Instantané figé dans `database/data/blue-lock-review-2026-11-20.json`.
 * Un joueur présent est mis à jour (stats, coût, poste) sans toucher à son portrait ni à sa description ;
 * seuls Sae Itoshi, Noel Noa et Michael Kaiser sont insérés s'ils sont absents.
 */
return new class extends Migration
{
    /** Met à jour les joueurs existants et insère les nouveaux. */
    public function up(): void
    {
        $players = json_decode(file_get_contents(database_path('data/blue-lock-review-2026-11-20.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($players): void {
            foreach ($players as $row) {
                $query = DB::table('players')->where('origin', $row['origin'])
                    ->where('firstname', $row['firstname'])->where('lastname', $row['lastname']);
                if ($query->exists()) {
                    $query->update([
                        'stats' => $row['stats'], 'heading' => $row['heading'], 'cost' => $row['cost'],
                        'position' => $row['position'], 'secondary_positions' => $row['secondary_positions'],
                        'updated_at' => now(),
                    ]);
                    continue;
                }
                // Seuls les trois nouveaux joueurs sont insérés : les autres ne sont que corrigés s'ils existent.
                if (in_array($row['lastname'], ['Itoshi', 'Noa', 'Kaiser'], true)) {
                    DB::table('players')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
                }
            }
        });
    }

    public function down(): void
    {
        // Les joueurs peuvent être référencés par une partie existante.
    }
};

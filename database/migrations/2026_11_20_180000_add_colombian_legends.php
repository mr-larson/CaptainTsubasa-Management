<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute quatorze légendes colombiennes (sélection volontairement faible, sauf le gardien Higuita), sans portrait pour l'instant.
 * Instantané figé dans `database/data/colombian-legends-2026-11-20.json`. Un joueur déjà présent n'est pas dupliqué.
 */
return new class extends Migration
{
    /** Insère les joueurs absents du catalogue. */
    public function up(): void
    {
        $players = json_decode(file_get_contents(database_path('data/colombian-legends-2026-11-20.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($players): void {
            // Nation du catalogue du mode Coupe du monde (drapeau dans public/storage/nations/co.svg).
            DB::table('nations')->insertOrIgnore([
                'name' => 'Colombie', 'code' => 'CO', 'flag_path' => 'nations/co.svg',
                'tactical_style' => 'possession', 'management_philosophy' => 'collective',
                'default_formation' => '4-2-1-3', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
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

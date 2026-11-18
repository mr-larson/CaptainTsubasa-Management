<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $players = json_decode(file_get_contents(database_path('data/mexico-aztec-warriors-2026-11-18.json')), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($players): void {
            foreach ($players as $row) {
                $query = DB::table('players')->where('origin', $row['origin'])->where('nationality', $row['nationality'])
                    ->where('firstname', $row['firstname'])->where('lastname', $row['lastname']);
                $existing = $query->first();
                if (!$existing) {
                    DB::table('players')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
                    continue;
                }

                // Réintégrer les anciens profils sans portrait archivés par la curation.
                // Préserver les statistiques, identifiants et portraits personnels existants.
                if ($existing->photo_path !== null && $existing->photo_path !== '') continue;
                if (!is_file(public_path('storage/'.$row['photo_path']))) continue;
                $query->update(['deleted_at' => null, 'photo_path' => $row['photo_path'], 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Conserver les identifiants et portraits : des parties peuvent les référencer.
    }
};

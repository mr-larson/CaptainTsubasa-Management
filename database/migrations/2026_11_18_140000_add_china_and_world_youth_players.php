<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('players')->exists()) return;

        $players = json_decode(file_get_contents(database_path('data/china-world-youth-2026-11-18.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($players): void {
            foreach ($players as $row) {
                // Les profils personnels et les joueurs archivés restent conservés.
                if (DB::table('players')->where('origin', $row['origin'])
                    ->where('firstname', $row['firstname'])->where('lastname', $row['lastname'])->exists()) continue;
                DB::table('players')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Conserver les identifiants qui peuvent être référencés par des parties.
    }
};

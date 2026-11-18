<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $players = json_decode(file_get_contents(database_path('data/senegal-players-2026-11-18.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($players): void {
            DB::table('nations')->insertOrIgnore([
                'name' => 'Sénégal', 'code' => 'SN', 'flag_path' => 'nations/sn.svg',
                'tactical_style' => 'balanced', 'management_philosophy' => 'collective',
                'default_formation' => '4-2-1-3', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($players as $row) {
                // Les profils personnalisés et les archives ne sont pas écrasés.
                if (DB::table('players')->where('origin', $row['origin'])->where('nationality', $row['nationality'])
                    ->where('firstname', $row['firstname'])->where('lastname', $row['lastname'])->exists()) continue;
                DB::table('players')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Une partie peut déjà référencer ces joueurs ou cette nation.
    }
};

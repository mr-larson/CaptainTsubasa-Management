<?php

use App\Enums\PlayerOrigin;
use Database\Seeders\PlayerSeeder;
use Database\Seeders\Players\InternationalLegends\Roster;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('players')->exists()) return;
        DB::transaction(function () {
            $seeder = new PlayerSeeder();
            $nationalities = Roster::nationalities();
            foreach (Roster::players() as $player) {
                // Inclure les profils archivés dans la détection : ne pas annuler les choix précédents.
                if (DB::table('players')->where('origin', PlayerOrigin::LEGENDS)
                    ->where('firstname', $player[0])->where('lastname', $player[1])->exists()) continue;
                $row = $seeder->row(PlayerOrigin::LEGENDS, $player, $nationalities, []);
                DB::table('players')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Les nouvelles références peuvent être utilisées dans des parties : conserver leurs ids.
    }
};

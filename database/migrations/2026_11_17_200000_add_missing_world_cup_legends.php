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

        // Ne pas réécrire les statistiques/ids existants ni réactiver les profils archivés.
        DB::transaction(function () {
            $seeder = new PlayerSeeder();
            $nationalities = Roster::nationalities();
            foreach (Roster::players() as $player) {
                if (DB::table('players')->where('origin', PlayerOrigin::LEGENDS)
                    ->where('firstname', $player[0])->where('lastname', $player[1])->exists()) continue;
                $row = $seeder->row(PlayerOrigin::LEGENDS, $player, $nationalities, []);
                DB::table('players')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Ces profils peuvent être référencés par des parties : conserver leurs ids.
    }
};

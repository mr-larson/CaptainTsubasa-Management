<?php

use App\Enums\PlayerOrigin;
use Database\Seeders\PlayerSeeder;
use Database\Seeders\Players\InternationalLegends\Countries\Belgium;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Base neuve : PlayerSeeder apporte déjà ces profils au premier seed.
        if (!DB::table('players')->exists()) return;

        DB::transaction(function () {
            $seeder = new PlayerSeeder();
            $nationalities = Belgium::nationalities();
            foreach (Belgium::players() as $player) {
                if (DB::table('players')->where('origin', PlayerOrigin::LEGENDS)
                    ->where('firstname', $player[0])->where('lastname', $player[1])->exists()) continue;
                $row = $seeder->row(PlayerOrigin::LEGENDS, $player, $nationalities, []);
                DB::table('players')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Conserver les profils et les ids qui peuvent être référencés par des parties.
    }
};

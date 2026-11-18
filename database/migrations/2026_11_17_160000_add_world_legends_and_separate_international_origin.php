<?php

use App\Enums\Nationality;
use App\Enums\PlayerOrigin;
use Database\Seeders\PlayerSeeder;
use Database\Seeders\Players\InternationalLegends\Roster;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Une base neuve reste vide jusqu'à db:seed, qui inclut déjà ce catalogue.
        if (!DB::table('players')->exists()) return;
        DB::transaction(function () {
            // Catalogue uniquement : les copies des joueurs des parties en cours
            // gardent leur origine, leurs statistiques et leur progression.
            DB::table('players')->where('origin', 'captain_tsubasa')
                ->whereNotNull('nationality')->where('nationality', '!=', Nationality::JAPON)
                ->update(['origin' => PlayerOrigin::INTERNATIONAL]);

            $seeder = new PlayerSeeder();
            $nationalities = Roster::nationalities();
            foreach (Roster::players() as $player) {
                // Ne pas réinitialiser ni écraser un profil déjà présent, même supprimé.
                if (DB::table('players')->where('origin', PlayerOrigin::LEGENDS)
                    ->where('firstname', $player[0])->where('lastname', $player[1])->exists()) continue;
                $row = $seeder->row(PlayerOrigin::LEGENDS, $player, $nationalities, []);
                DB::table('players')->insert($row + ['created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Les nouveaux joueurs peuvent être référencés par des parties créées
        // depuis le déploiement : ne pas supprimer le catalogue ni réécrire ses ids.
    }
};

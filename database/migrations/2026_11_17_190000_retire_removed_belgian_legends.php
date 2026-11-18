<?php

use App\Enums\PlayerOrigin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Liste figée : ne jamais déduire des suppressions d'un seeder évolutif.
        $removed = [
            ['Dries', 'Mertens'], ['Toby', 'Alderweireld'], ['Mousa', 'Dembélé'],
            ['Marouane', 'Fellaini'], ['Radja', 'Nainggolan'], ['Axel', 'Witsel'],
            ['Lei', 'Clijsters'], ['Yannick', 'Carrasco'], ['Nacer', 'Chadli'],
            ['Thomas', 'Meunier'], ['Christian', 'Benteke'], ['Kevin', 'Mirallas'],
            ['Loïs', 'Openda'], ['Charles', 'De Ketelaere'], ['Amadou', 'Onana'],
        ];

        DB::transaction(function () use ($removed) {
            foreach ($removed as [$first, $last]) {
                DB::table('players')->where('origin', PlayerOrigin::LEGENDS)
                    ->where('nationality', 'Belgique')->where('firstname', $first)->where('lastname', $last)
                    ->whereNull('deleted_at')->update(['deleted_at' => now(), 'updated_at' => now()]);
            }
        });
        // Aucun effacement physique : ids, liens, photos et copies game_players sont conservés.
    }

    public function down(): void
    {
        // Ne pas réactiver des profils que l'utilisateur a choisi de retirer.
    }
};

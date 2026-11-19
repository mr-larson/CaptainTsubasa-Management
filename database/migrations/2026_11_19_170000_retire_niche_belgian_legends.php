<?php

use App\Enums\PlayerOrigin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retire dix-sept légendes belges jugées trop confidentielles pour le jeu. Suppression logique :
 * ids, liens, portraits et copies de parties sont conservés. Liste figée, jamais déduite du seeder.
 */
return new class extends Migration
{
    /** Archive les profils retirés s'ils existent encore. */
    public function up(): void
    {
        $removed = [
            ['Logan', 'Bailly'], ['Stijn', 'Stijnen'], ['Geert', 'De Vlieger'],
            ['Birger', 'Maertens'], ['Rudi', 'Smidts'], ['Nicolas', 'Lombaerts'], ['Carl', 'Hoefkens'],
            ['Robby', 'Van de Weyer'], ['Bernd', 'Thijs'],
            ['Luigi', 'Pieroni'], ['Cédric', 'Roussel'], ['Mémé', 'Tchité'], ['François', 'Sterchele'],
            ['Stéphane', 'Demol'], ['Alexandre', 'Czerniatynski'], ['Branko', 'Strupar'], ['Jelle', 'Van Damme'],
        ];

        DB::transaction(function () use ($removed) {
            foreach ($removed as [$first, $last]) {
                DB::table('players')->where('origin', PlayerOrigin::LEGENDS)
                    ->where('nationality', 'Belgique')->where('firstname', $first)->where('lastname', $last)
                    ->whereNull('deleted_at')->update(['deleted_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Ne pas réactiver des profils que l'utilisateur a choisi de retirer.
    }
};

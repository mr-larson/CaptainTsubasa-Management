<?php

use App\Enums\Nationality;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['players', 'game_players'] as $table) {
            foreach ([
                ['Juan', 'Babington', Nationality::ARGENTINE],
                ['', 'Haan', Nationality::PAYS_BAS],
            ] as [$firstname, $lastname, $nationality]) {
                DB::table($table)
                    ->where('origin', 'captain_tsubasa')
                    ->where('firstname', $firstname)
                    ->where('lastname', $lastname)
                    ->where(fn ($query) => $query->whereNull('nationality')->orWhere('nationality', Nationality::JAPON))
                    ->update(['nationality' => $nationality]);
            }
        }
    }

    public function down(): void
    {
        // Les nationalités corrigées restent valides après un retour de version.
    }
};

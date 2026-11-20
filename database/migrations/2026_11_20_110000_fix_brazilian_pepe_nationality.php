<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le Pepe de Captain Tsubasa est brésilien. Il avait la nationalité portugaise, car son slug (« pepe »)
 * est le même que celui du Pepe portugais des légendes réelles, dont la nationalité l'emportait au seed.
 */
return new class extends Migration
{
    /** Remet le Pepe de Captain Tsubasa en nationalité brésilienne. */
    public function up(): void
    {
        DB::table('players')->where('origin', 'captain_tsubasa_international')
            ->where('firstname', '')->where('lastname', 'Pepe')->where('nationality', 'Portugal')
            ->update(['nationality' => 'Brésil']);
    }

    public function down(): void
    {
        // Ne pas rétablir une nationalité erronée.
    }
};

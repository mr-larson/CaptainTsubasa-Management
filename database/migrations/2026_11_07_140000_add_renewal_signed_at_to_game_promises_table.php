<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute à `game_promises` l'horodatage nullable `renewal_signed_at`, renseigné quand la promesse de renouvellement a été honorée par une signature.
 */
return new class extends Migration
{
    /** Ajoute la date de signature du renouvellement. */
    public function up(): void
    {
        Schema::table('game_promises', function (Blueprint $table) {
            $table->timestamp('renewal_signed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('game_promises', function (Blueprint $table) {
            $table->dropColumn('renewal_signed_at');
        });
    }
};

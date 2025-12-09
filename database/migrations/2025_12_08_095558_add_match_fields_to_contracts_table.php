<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // On garde salary mais on le considère comme "coût par match"
            $table->unsignedInteger('matches_total')->default(1)->after('salary');
            $table->unsignedInteger('matches_played')->default(0)->after('matches_total');

            // Optionnel : rendre les dates facultatives si tu veux les garder
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('matches_total');
            $table->dropColumn('matches_played');

            // Si tu veux revenir à l'ancien comportement
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
        });
    }
};

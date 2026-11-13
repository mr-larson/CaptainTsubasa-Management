<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table `teams` du catalogue de référence : nom unique, description, budget et bilan (victoires/nuls/défaites), avec suppression logique et un index de recherche.
 */
class CreateTeamsTable extends Migration
{
    /** Crée la table des équipes de référence. */
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('budget')->default(0);
            $table->unsignedSmallInteger('wins')->default(0);
            $table->unsignedSmallInteger('draws')->default(0);
            $table->unsignedSmallInteger('losses')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['name', 'budget', 'wins', 'draws', 'losses']);
        });


    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
}

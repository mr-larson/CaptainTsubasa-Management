<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table `players` du catalogue de référence : identité, âge, poste, coût, statistiques (JSON) et description, avec suppression logique et un index sur nom/poste.
 */
class CreatePlayersTable extends Migration
{
    /** Crée la table des joueurs de référence. */
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('firstname', 255);
            $table->string('lastname', 255);
            $table->integer('age');
            $table->string('position', 100);
            $table->integer('cost')->default(0);
            $table->json('stats');
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['firstname', 'lastname', 'position']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
}

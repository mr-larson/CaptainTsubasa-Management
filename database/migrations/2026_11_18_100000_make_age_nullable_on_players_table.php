<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rend `players.age` facultative : le champ n'est plus affiché dans les formulaires de joueur (usage incertain), donc un joueur créé sans âge doit rester valide. `down()` ne rétablit pas le NOT NULL (des lignes sans âge peuvent exister).
 */
return new class extends Migration
{
    /** Passe la colonne `age` en nullable. */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->integer('age')->nullable()->change();
        });
    }

    /** Sans effet : on ne remet pas NOT NULL pour ne pas échouer sur des âges vides. */
    public function down(): void
    {
    }
};

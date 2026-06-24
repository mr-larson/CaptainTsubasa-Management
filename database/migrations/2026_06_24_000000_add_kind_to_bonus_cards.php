<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonus_cards', function (Blueprint $table) {
            // Distingue les cartes bénéfiques (bonus) des cartes offensives (malus).
            $table->enum('kind', ['bonus', 'malus'])->default('bonus')->after('description');
        });

        // Nouvelle cible "opponent" : la carte vise l'adversaire du prochain match.
        DB::statement("ALTER TABLE bonus_cards MODIFY COLUMN target ENUM('self','player','match','finance','opponent') NOT NULL");
    }

    public function down(): void
    {
        // Repli des cartes malus sur "self" avant de retirer la valeur d'enum.
        DB::table('bonus_cards')->where('target', 'opponent')->update(['target' => 'self']);
        DB::statement("ALTER TABLE bonus_cards MODIFY COLUMN target ENUM('self','player','match','finance') NOT NULL");

        Schema::table('bonus_cards', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};

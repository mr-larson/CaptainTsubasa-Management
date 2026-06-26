<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Nouvelle cible "team" : malus visant une équipe choisie (ou le leader).
        DB::statement("ALTER TABLE bonus_cards MODIFY COLUMN target ENUM('self','player','match','finance','opponent','team') NOT NULL");
    }

    public function down(): void
    {
        DB::table('bonus_cards')->where('target', 'team')->update(['target' => 'opponent']);
        DB::statement("ALTER TABLE bonus_cards MODIFY COLUMN target ENUM('self','player','match','finance','opponent') NOT NULL");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            // JSON nullable, après description (logique RP / profil joueur)
            $table->json('special_moves')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn('special_moves');
        });
    }
};

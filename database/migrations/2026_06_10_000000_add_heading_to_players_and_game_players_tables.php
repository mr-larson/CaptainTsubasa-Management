<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->unsignedTinyInteger('heading')->default(15)->after('stats');
        });

        Schema::table('game_players', function (Blueprint $table) {
            $table->unsignedTinyInteger('heading')->default(15)->after('tackle');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('heading');
        });

        Schema::table('game_players', function (Blueprint $table) {
            $table->dropColumn('heading');
        });
    }
};

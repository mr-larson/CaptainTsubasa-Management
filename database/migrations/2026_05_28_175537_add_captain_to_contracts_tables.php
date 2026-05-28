<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->boolean('is_captain')->default(false)->after('is_starter');
        });

        Schema::table('game_contracts', function (Blueprint $table) {
            $table->boolean('is_captain')->default(false)->after('is_starter');
            $table->unsignedTinyInteger('captain_rerolls_remaining')->default(3)->after('is_captain');
            $table->boolean('captain_reroll_used_this_action')->default(false)->after('captain_rerolls_remaining');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('is_captain');
        });

        Schema::table('game_contracts', function (Blueprint $table) {
            $table->dropColumn(['is_captain', 'captain_rerolls_remaining', 'captain_reroll_used_this_action']);
        });
    }
};

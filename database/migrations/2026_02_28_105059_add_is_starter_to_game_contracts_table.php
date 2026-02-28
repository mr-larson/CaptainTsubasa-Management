<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_contracts', function (Blueprint $table) {
            $table->boolean('is_starter')->default(true)->after('end_week');
        });
    }

    public function down(): void
    {
        Schema::table('game_contracts', function (Blueprint $table) {
            $table->dropColumn('is_starter');
        });
    }
};

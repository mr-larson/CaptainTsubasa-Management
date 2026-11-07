<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_promises', function (Blueprint $table) {
            $table->timestamp('renewal_signed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('game_promises', function (Blueprint $table) {
            $table->dropColumn('renewal_signed_at');
        });
    }
};

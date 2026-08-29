<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->unsignedInteger('player_count')->default(0);
            $table->unsignedInteger('team_count')->default(0);
            $table->json('teams_json');
            $table->json('players_json');
            $table->json('contracts_json');
            $table->timestamps();

            $table->index('is_shared');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_packages');
    }
};

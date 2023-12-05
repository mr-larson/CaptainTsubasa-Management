<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamsTable extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('budget')->default(0);
            $table->unsignedSmallInteger('wins')->default(0);
            $table->unsignedSmallInteger('draws')->default(0);
            $table->unsignedSmallInteger('losses')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['name', 'budget', 'wins', 'draws', 'losses']);
        });


    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
}

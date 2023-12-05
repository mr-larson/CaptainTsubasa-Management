<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayersTable extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('firstname', 255);
            $table->string('lastname', 255);
            $table->integer('age');
            $table->string('position', 100);
            $table->integer('cost')->default(0);
            $table->json('stats');
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['firstname', 'lastname', 'position']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
}

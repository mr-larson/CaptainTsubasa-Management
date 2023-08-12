<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['bonus', 'malus']);
            $table->string('name')->unique();
            $table->text('description');
            $table->json('effects'); // Contiendra un objet JSON: { "tir": 10%, "passe": -5%, ... }
            $table->integer('duration_in_weeks')->default(1); // Durée d'efficacité de la carte en semaines
            $table->timestamps();
            $table->softDeletes();
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};

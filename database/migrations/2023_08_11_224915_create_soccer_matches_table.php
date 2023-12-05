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
        Schema::create('soccer_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id_home');
            $table->unsignedBigInteger('team_id_away');
            $table->integer('score_team_home')->nullable();
            $table->integer('score_team_away')->nullable();
            $table->dateTime('date');

            $table->foreign('team_id_home')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('team_id_away')->references('id')->on('teams')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id_home', 'team_id_away', 'date', 'score_team_home', 'score_team_away'], 'match_details_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soccer_matches');
    }
};

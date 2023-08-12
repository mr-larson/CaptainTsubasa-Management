<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyTrainingsAndTrainingTypesTables extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->date('training_date')->after('id');
            $table->dropColumn('stat_increase');
            $table->dropColumn('player_ids');
        });

        Schema::table('training_types', function (Blueprint $table) {
            $table->json('stat_increase')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->json('stat_increase')->after('training_type_id');
            $table->json('player_ids')->after('training_type_id');
            $table->dropColumn('training_date');
        });

        Schema::table('training_types', function (Blueprint $table) {
            $table->dropColumn('stat_increase');
        });
    }
}


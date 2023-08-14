<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyInjuryRiskOnPlayersTable extends Migration
{
    public function up()
    {
        Schema::table('players', function (Blueprint $table) {
            $table->float('injury_risk', 5, 2)->default(0.0)->change();
        });
    }

    public function down()
    {
        Schema::table('players', function (Blueprint $table) {
            $table->float('injury_risk', 3, 2)->default(0.0)->change();  // Revert to the old definition
        });
    }
}

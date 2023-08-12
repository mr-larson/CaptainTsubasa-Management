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
        Schema::table('soccer_matches', function (Blueprint $table) {
             // Cartes Promo pour chaque équipe
             $table->json('team_a_promo_cards')->nullable()->after('highlights');
             $table->json('team_b_promo_cards')->nullable()->after('team_a_promo_cards');
 
             // Fatigue des joueurs avant et après le match
             $table->json('team_a_pre_match_fatigue')->nullable()->after('team_b_promo_cards');
             $table->json('team_b_pre_match_fatigue')->nullable()->after('team_a_pre_match_fatigue');
             $table->json('team_a_post_match_fatigue')->nullable()->after('team_b_pre_match_fatigue');
             $table->json('team_b_post_match_fatigue')->nullable()->after('team_a_post_match_fatigue');
 
             // Joueurs blessés pendant le match
             $table->json('injured_players')->nullable()->after('team_b_post_match_fatigue');
 
             // Gains financiers pour chaque équipe
             $table->decimal('team_a_financial_gain', 10, 2)->default(0)->after('injured_players');
             $table->decimal('team_b_financial_gain', 10, 2)->default(0)->after('team_a_financial_gain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('soccer_match', function (Blueprint $table) {
            $table->dropColumn('team_a_promo_cards');
            $table->dropColumn('team_b_promo_cards');
            $table->dropColumn('team_a_pre_match_fatigue');
            $table->dropColumn('team_b_pre_match_fatigue');
            $table->dropColumn('team_a_post_match_fatigue');
            $table->dropColumn('team_b_post_match_fatigue');
            $table->dropColumn('injured_players');
            $table->dropColumn('team_a_financial_gain');
            $table->dropColumn('team_b_financial_gain');
        });
    }
};

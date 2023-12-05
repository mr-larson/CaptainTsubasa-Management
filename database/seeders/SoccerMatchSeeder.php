<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SoccerMatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les ID des deux premières équipes
        $teamIdHome = DB::table('teams')->first()->id;
        $teamIdAway = DB::table('teams')->orderBy('id', 'asc')->skip(1)->first()->id;

        DB::table('soccer_matches')->insert([
            'team_id_home' => $teamIdHome,
            'team_id_away' => $teamIdAway,
            'score_team_home' => 3, // Score de l'équipe à domicile
            'score_team_away' => 1, // Score de l'équipe à l'extérieur
            'date' => Carbon::now(), // Date et heure actuelles
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}

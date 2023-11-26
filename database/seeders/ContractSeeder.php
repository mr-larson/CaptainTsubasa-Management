<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Récupérer tous les joueurs
        $players = Player::all();

        // Récupérer toutes les équipes
        $teams = Team::all();

        // Supposons que chaque équipe ait un nombre égal de joueurs
        $playersPerTeam = intdiv($players->count(), $teams->count());

        foreach ($teams as $team) {
            // Sélectionner un sous-ensemble aléatoire de joueurs pour chaque équipe
            $teamPlayers = $players->random($playersPerTeam);

            // Assigner chaque joueur sélectionné à l'équipe
            foreach ($teamPlayers as $player) {
                DB::table('contracts')->insert([
                    'player_id' => $player->id,
                    'team_id' => $team->id,
                    'salary' => $player->cost, // Utiliser le coût du joueur comme salaire
                    'start_date' => now()
                        ->subMonths(rand(1, 12))
                        ->subDays(rand(1, 30)),
                    'end_date' => now()
                        ->addMonths(rand(1, 12))
                        ->addDays(rand(1, 30)),
                ]);

                // Supprimer le joueur de la collection pour éviter les doublons
                $players = $players->reject(function ($p) use ($player) {
                    return $p->id == $player->id;
                });
            }
        }
    }
}

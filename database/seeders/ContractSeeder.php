<?php

namespace Database\Seeders;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // On nettoie la table des contrats
        Schema::disableForeignKeyConstraints();
        DB::table('contracts')->truncate();
        Schema::enableForeignKeyConstraints();

        // Define teams and their corresponding players (par nom de famille)
        $teamsPlayers = [
            'Nankatsu' => ['Morisaki', 'Tsuboi', 'Ishizaki', 'Nakazato', 'Nagano', 'Okawa', 'Sakurai', 'Ozora', 'Misaki', 'Oda', 'Iwami', 'Minowa', 'Murashige'],

            'Toho' => ['Wakashimazu', 'Furuta', 'Kawabe', 'Takashima', 'Imai', 'Koike', 'Matsuki', 'Sawada', 'Shimano', 'Hyuga', 'Sorimachi'],

            'Hanawa' => ['Yoshikura', 'Koda', 'Murasawa', 'Nakamura', 'Daimaru', 'Shiota', 'Aimoto', 'Tamai', 'Tachibana', 'Ono'],

            'Furano' => ['Kato', 'Honda', 'Kondo', 'Kamata', 'Matsuda', 'Kaneda', 'Matsuyama', 'Wakamatsu', 'Nakagawa', 'Oda', 'Yamamuro'],

            'Otomo' => ['Ichijo', 'Yoshikawa', 'Nishio', 'Nakayama', 'Kawada', 'Hiraoka', 'Kishida', 'Urabe', 'Tadami', 'Nakao', 'Nitta'],

            'Azumaichi' => ['Tsuji', 'Soda', 'Yamada', 'Sasaki', 'Hayashi', 'Yoshida', 'Kuramochi', 'Nakai', 'Onodera', 'Ide', 'Mihashi'],

            'Musashi' => ['Moriyama', 'Kido', 'Mukai', 'Sano', 'Suzuki', 'Yoshida', 'Inoue', 'Misugi', 'Ichinose', 'Honma', 'Sanada'],

            'Shutetsu' => ['Wakabayashi', 'Takasugi', 'Shimada', 'Matsumo', 'Nakamoto', 'Kurata', 'Osaki', 'Inamura', 'Izawa', 'Kisugi', 'Taki'],

            'Meiwa' => ['Murasawa', 'Kawagoe', 'Ishii', 'Takagi', 'Nagano', 'Sakamoto', 'Narita', 'Hori', 'Enomoto', 'Sawaki', 'Suenaga'],

            'Nakahara' => ['Kawakami', 'Masumoto', 'Haranashi', 'Fujita', 'Toda', 'Nagatani', 'Harukawa', 'Itao', 'Kurita', 'Asada', 'Aoi'],

            'Shimizu' => ['Kawakami', 'Kudo', 'Kanda', 'Ibaraki', 'Suzuki', 'Takada', 'Nakao', 'Iimura', 'Murakami', 'Kato', 'Obayashi'],

            'Shimada' => ['Nagai', 'Ito', 'Fujisawa', 'Takahashi', 'Kimura', 'Ishikawa', 'Hashimoto', 'Nagasaki', 'Nakamura', 'Jinbo', 'Wesugi'],

            'Hirado' => ['Hatakeyama', 'Soda', 'Jito', 'Akizawa', 'Noda', 'Nagaoka', 'Nakajo', 'Morisue', 'Takeno', 'Himeji', 'Sano'],

            'Naniwa' => ['Nakanishi', 'Tsusaki', 'Kosaka', 'Yoshimoto', 'Tennoji', 'Dojima', 'Maeda', 'Shirai', 'Ogami', 'Takayanagi', 'Marui'],

            'Minawi' => ['Asakura', 'Azuma', 'Takahama', 'Kawanoe', 'Iyo', 'Tosa', 'Shintani', 'Ishida', 'Hirayama', 'Seto', 'Takei'],
        ];

        // Pour éviter d’assigner deux fois le même joueur si un nom existe en double
        $alreadyAssignedPlayerIds = [];

        foreach ($teamsPlayers as $teamName => $playerLastnames) {
            /** @var \App\Models\Team|null $team */
            $team = Team::where('name', $teamName)->first();

            if (! $team) {
                // Option douce : on skippe si l'équipe n'existe pas encore
                // tu peux remplacer par un throw si tu préfères que ça pète en dev
                // throw new \Exception("Team '{$teamName}' not found");
                continue;
            }

            foreach ($playerLastnames as $lastname) {
                /** @var \App\Models\Player|null $player */
                $playerQuery = Player::where('lastname', $lastname);

                // Si tu veux être ultra strict, tu peux ajouter des conditions
                // par exemple sur l'âge ou sur le coût minimum/maximum.

                $player = $playerQuery->first();

                if (! $player) {
                    // Même chose : tu peux logger ou throw ici si un nom ne matche rien
                    // logger("Player with lastname '{$lastname}' not found for team '{$teamName}'");
                    continue;
                }

                // éviter les doublons si un même joueur pourrait être ciblé deux fois
                if (in_array($player->id, $alreadyAssignedPlayerIds, true)) {
                    continue;
                }

                $alreadyAssignedPlayerIds[] = $player->id;

                DB::table('contracts')->insert([
                    'player_id' => $player->id,
                    'team_id'   => $team->id,
                    'salary'    => $player->cost, // Utiliser le coût du joueur comme salaire
                    'start_date' => now()
                        ->subMonths(rand(1, 12))
                        ->subDays(rand(1, 30)),
                    'end_date'   => now()
                        ->addMonths(rand(1, 12))
                        ->addDays(rand(1, 30)),
                ]);
            }
        }
    }
}

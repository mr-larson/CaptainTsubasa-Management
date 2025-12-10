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
        // On nettoie les contrats avant de reseed
        Schema::disableForeignKeyConstraints();
        DB::table('contracts')->truncate();
        Schema::enableForeignKeyConstraints();

        // Map des équipes par nom -> id
        $teamsByName = Team::pluck('id', 'name');

        // Map des joueurs par "Prénom Nom" -> Player
        $playersByFullName = Player::all()->keyBy(function (Player $player) {
            return "{$player->firstname} {$player->lastname}";
        });

        // Define teams and their corresponding players (par nom complet)
        $teamsPlayers = [
            'Nankatsu' => [
                'Yuzo Morisaki',
                'Akira Tsuboi',
                'Ryo Ishizaki',
                'Masato Nakazato',
                'Hiroshi Nagano',
                'Manabu Okawa',
                'Susumu Sakurai',
                'Tsubasa Ozora',
                'Taro Misaki',
                'Tsuyoshi Oda',
                'Kenichi Iwami',
                'Shota Minowa',
                'Yutaka Murashige',
            ],

            'Shutetsu' => [
                'Genzo Wakabayashi',
                'Shingo Takasugi',
                'Kenta Shimada',
                'Kazuma Matsumo',
                'Kohei Nakamoto',
                'Jun Kurata',
                'Takumi Osaki',
                'Kaito Inamura',
                'Mamoru Izawa',
                'Teppei Kisugi',
                'Hajime Taki',
            ],

            'Toho' => [
                'Ken Wakashimazu',
                'Kiyoshi Furuta',
                'Katsuji Kawabe',
                'Tsuneo Takashima',
                'Hiroshi Imai',
                'Hideto Koike',
                'Yutaka Matsuki',
                'Takeshi Sawada',
                'Tadashi Shimano',
                'Kojiro Hyuga',
                'Kazuki Sorimachi',
            ],

            'Furano' => [
                'Masanori Kato',
                'Susumu Honda',
                'Tsuyoshi Kondo',
                'Kentaro Kamata',
                'Hisashi Matsuda',
                'Haruo Kaneda',
                'Hikaru Matsuyama',
                'Koichi Wakamatsu',
                'Seiji Nakagawa',
                'Kazumasa Oda',
                'Shuichi Yamamuro',
            ],

            'Musashi' => [
                'Tsutomu Moriyama',
                'Osamu Kido',
                'Hiroshi Mukai',
                'Ryoichi Sano',
                'Shinichi Suzuki',
                'Kensaku Yoshida',
                'Shota Inoue',
                'Jun Misugi',
                'Akira Ichinose',
                'Minoru Honma',
                'Shinji Sanada',
            ],

            'Hanawa' => [
                'Kimio Yoshikura',
                'Masaru Koda',
                'Yusuaki Murasawa',
                'Norio Nakamura',
                'Yuichiro Daimaru',
                'Takayuki Shiota',
                'Nobuo Aimoto',
                'Hiroshi Tamai',
                'Masao Tachibana',
                'Kazuo Tachibana',
                'Yoshiharu Ono',
            ],

            'Azumaichi' => [
                'Ryota Tsuji',
                'Makoto Soda',
                'Junji Yamada',
                'Daigo Sasaki',
                'Tatsuya Hayashi',
                'Koji Yoshida',
                'Yohei Kuramochi',
                'Toru Nakai',
                'Kazuyasu Onodera',
                'Mitsuru Ide',
                'Shohei Mihashi',
            ],

            'Hirado' => [
                'Akira Hatakeyama',
                'Kazuaki Soda',
                'Hiroshi Jito',
                'Toshio Akizawa',
                'Shinji Noda',
                'Tsutomu Nagaoka',
                'Koji Nakajo',
                'Shinji Morisue',
                'Kazuo Takeno',
                'Katsumi Himeji',
                'Mitsuru Sano',
            ],

            'Otomo' => [
                'Isamu Ichijo',
                'Masaki Yoshikawa',
                'Koji Nishio',
                'Masao Nakayama',
                'Kozo Kawada',
                'Toru Hiraoka',
                'Takeshi Kishida',
                'Hanji Urabe',
                'Shingo Tadami',
                'Akio Nakao',
                'Shun Nitta',
            ],

            'Meiwa' => [
                'Tetsuji Murasawa',
                'Keiji Kawagoe',
                'Hiroshi Ishii',
                'Toshiyuki Takagi',
                'Motoharu Nagano',
                'Shinishi Sakamoto',
                'Kuniaki Narita',
                'Hiromichi Hori',
                'Kazushige Enomoto',
                'Noboru Sawaki',
                'Yuichi Suenaga',
            ],

            'Nakahara' => [
                'Goro Kawakami',
                'Yuichi Masumoto',
                'Keisuke Haranashi',
                'Takamasa Fujita',
                'Jin Toda',
                'Ken Nagatani',
                'Shunta Harukawa',
                'Susumu Itao',
                'Goro Kurita',
                'Takeshi Asada',
                'Shingo Aoi',
            ],

            'Naniwa' => [
                'Taichi Nakanishi',
                'Hiroshi Tsusaki',
                'Kazuya Kosaka',
                'Shinji Yoshimoto',
                'Daisuke Tennoji',
                'Masato Dojima',
                'Ryo Maeda',
                'Kenji Shirai',
                'Yuta Ogami',
                'Satoshi Takayanagi',
                'Tetsuya Marui',
            ],

            'Minawi' => [
                'Hajime Asakura',
                'Daichi Azuma',
                'Shinji Takahama',
                'Ryu Kawanoe',
                'Takashi Iyo',
                'Koji Tosa',
                'Hiroto Shintani',
                'Tetsuo Ishida',
                'Masaru Hirayama',
                'Kazuki Seto',
                'Kazuto Takei',
            ],

            'Shimizu' => [
                'Morimichi Kawakami',
                'Takeshi Kudo',
                'Ichiro Kanda',
                'Yuto Ibaraki',
                'Hiroshi Suzuki',
                'Daisuke Takada',
                'Ryota Nakao',
                'Shinji Iimura',
                'Koji Murakami',
                'Kazumasa Kato',
                'Takashi Obayashi',
            ],

            'Shimada' => [
                'Etsuo Nagai',
                'Ikushi Ito',
                'Koichi Fujisawa',
                'Nemto Takahashi',
                'Jo Kimura',
                'Koji Ishikawa',
                'Takushi Hashimoto',
                'Junichi Nagasaki',
                'Light Nakamura',
                'Masayuki Jinbo',
                'Naoki Wesugi',
            ],
        ];

        $contracts = [];

        foreach ($teamsPlayers as $teamName => $playerNames) {
            $teamId = $teamsByName[$teamName] ?? null;

            if (! $teamId) {
                // Optionnel: log si une équipe n'existe pas
                dump("Team not found for name: {$teamName}");
                continue;
            }

            foreach ($playerNames as $fullName) {
                /** @var Player|null $player */
                $player = $playersByFullName->get($fullName);

                if (! $player) {
                    // Optionnel: log si un joueur n'existe pas
                    dump("Player not found for full name: {$fullName}");
                    continue;
                }

                $contracts[] = [
                    'player_id'  => $player->id,
                    'team_id'    => $teamId,
                    'salary'     => $player->cost,
                    'start_date' => now()
                        ->subMonths(rand(1, 12))
                        ->subDays(rand(1, 30)),
                    'end_date'   => now()
                        ->addMonths(rand(1, 12))
                        ->addDays(rand(1, 30)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($contracts)) {
            DB::table('contracts')->insert($contracts);
        }
    }
}

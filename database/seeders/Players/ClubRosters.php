<?php

namespace Database\Seeders\Players;

/**
 * Effectifs officiels des 15 clubs de Captain Tsubasa (collège) pour les
 * contrats de référence : quels joueurs jouent dans quel club, et qui est
 * capitaine. Lu par ContractSeeder, qui les transforme en lignes `contracts`.
 *
 * Les noms sont « Prénom Nom » comme dans le catalogue de joueurs
 * (database/seeders/Players/Clubs/). Pour chaque club : les 11 premiers sont
 * les titulaires, les 3 derniers les remplaçants.
 */
final class ClubRosters
{
    /**
     * Effectif de chaque club, indexé par le nom du club (table `teams`).
     * Titulaires (11) puis remplaçants (3).
     *
     * @return array<string, array<int, string>>
     */
    public static function players(): array
    {
        return [
            'Nankatsu' => [
                // Titulaires (11)
                'Yuzo Morisaki', 'Masato Nakazato',  'Ryo Ishizaki',
                'Hiroshi Nagano', 'Susumu Sakurai','Tsuyoshi Oda', 'Shota Minowa',
                'Taro Misaki', 'Kenichi Iwami', 'Tsubasa Ozora', 'Yutaka Murashige',
                // Remplaçants (3)
                'Akira Tsuboi', 'Manabu Okawa', 'Kenji Tomo'
            ],
            'Shutetsu' => [
                'Genzo Wakabayashi', 'Kenta Shimada', 'Shingo Takasugi', 'Wanatabe Matsumo',
                'Kohei Nakamoto', 'Yoshioka Kurata', 'Akiyoshi Osaki', 'Kitajima Inamura',
                'Mamoru Izawa', 'Teppei Kisugi', 'Hajime Taki',
                // Remplaçants (3)
                'Hiroki Fujii', 'Daisuke Mori', 'Yosuke Kimura',
            ],
            'Toho' => [
                'Ken Wakashimazu', 'Kiyoshi Furuta', 'Katsuji Kawabe', 'Tsuneo Takashima',
                'Hiroshi Imai', 'Hideto Koike', 'Yutaka Matsuki', 'Takeshi Sawada',
                'Tadashi Shimano', 'Kojiro Hyuga', 'Kazuki Sorimachi',
                // Remplaçants (3)
                'Ryuji Endo', 'Masashi Goto', 'Keita Ogawa',
            ],
            'Furano' => [
                'Masanori Kato', 'Susumu Honda', 'Tsuyoshi Kondo', 'Kentaro Kamata',
                'Hisashi Matsuda', 'Haruo Kaneda', 'Koichi Wakamatsu', 'Hikaru Matsuyama',
                'Seiji Nakagawa', 'Kazumasa Oda', 'Shuichi Yamamuro',
                // Remplaçants (3)
                'Sosuke Maeda', 'Tetsuya Iida', 'Noboru Hayashi',
            ],
            'Musashi' => [
                'Tsutomu Moriyama', 'Osamu Kido', 'Hiroshi Mukai', 'Ryoichi Sano',
                'Shinichi Suzuki', 'Kensaku Yoshida', 'Shota Inoue', 'Jun Misugi',
                'Akira Ichinose', 'Minoru Honma', 'Shinji Sanada',
                // Remplaçants (3)
                'Hiroyuki Noda', 'Junpei Aoki', 'Satoru Koyama',
            ],
            'Hanawa' => [
                'Kimio Yoshikura', 'Masaru Koda', 'Yusuaki Murasawa', 'Norio Nakamura',
                'Yuichiro Daimaru', 'Takayuki Shiota', 'Nobuo Aimoto', 'Hiroshi Tamai',
                'Masao Tachibana', 'Kazuo Tachibana', 'Yoshiharu Ono',
                // Remplaçants (3)
                'Takuro Fujimoto', 'Yuki Ozawa', 'Osamu Miyata',
            ],
            'Azumaichi' => [
                'Ryota Tsuji', 'Junji Yamada', 'Makoto Soda', 'Daigo Sasaki',
                'Tatsuya Hayashi', 'Koji Yoshida', 'Yohei Kuramochi', 'Toru Nakai',
                'Kazuyasu Onodera', 'Mitsuru Ide', 'Shohei Mihashi',
                // Remplaçants (3)
                'Kengo Ueda', 'Shohei Kawai', 'Tomoya Ishida',
            ],
            'Hirado' => [
                'Akira Hatakeyama', 'Kazuaki Soda', 'Hiroshi Jito', 'Toshio Akizawa',
                'Shinji Noda', 'Tsutomu Nagaoka', 'Koji Nakajo', 'Shinji Morisue',
                'Kazuo Takeno', 'Katsumi Himeji', 'Mitsuru Sano',
                // Remplaçants (3)
                'Makoto Fukuda', 'Ryo Kawamoto', 'Tatsuya Kimura',
            ],
            'Otomo' => [
                'Isamu Ichijo', 'Masaki Yoshikawa', 'Koji Nishio', 'Masao Nakayama',
                'Kozo Kawada', 'Hanji Urabe', 'Takeshi Kishida', 'Toru Hiraoka',
                'Shingo Tadami', 'Akio Nakao', 'Shun Nitta',
                // Remplaçants (3)
                'Shunsuke Wada', 'Yuya Saeki', 'Kazuki Hara',
            ],
            'Meiwa' => [
                'Tetsuji Murasawa', 'Keiji Kawagoe', 'Hiroshi Ishii', 'Toshiyuki Takagi',
                'Motoharu Nagano', 'Shinishi Sakamoto', 'Kuniaki Narita', 'Hiromichi Hori',
                'Kazushige Enomoto', 'Noboru Sawaki', 'Yuichi Suenaga',
                // Remplaçants (3)
                'Tomohiro Abe', 'Nobuhiro Suzuki', 'Fumihiro Kato',
            ],
            'Nakahara' => [
                'Goro Kawakami', 'Kohei Hori', 'Takamasa Fujita', 'Jin Toda',
                'Keisuke Haranashi', 'Ken Nagatani', 'Shunta Harukawa', 'Susumu Itao',
                'Goro Kurita', 'Takashi Hatori', 'Shingo Aoi',
                // Remplaçants (3)
                'Ikki Miura', 'Toshiki Ono', 'Hiroki Tanaka',
            ],
            'Naniwa' => [
                'Taichi Nakanishi', 'Hiroshi Tsusaki', 'Kazuya Kosaka', 'Shinji Yoshimoto',
                'Daisuke Tennoji', 'Masato Dojima', 'Ryo Maeda', 'Kenji Shirai',
                'Yuta Ogami', 'Satoshi Takayanagi', 'Tetsuya Marui',
                // Remplaçants (3)
                'Atsushi Goto', 'Kohei Imai', 'Daizo Shimizu',
            ],
            'Minawi' => [
                'Hajime Asakura', 'Daichi Azuma', 'Shinji Takahama', 'Ryu Kawanoe',
                'Takashi Iyo', 'Koji Tosa', 'Hiroto Shintani', 'Tetsuo Ishida',
                'Masaru Hirayama', 'Kazuki Seto', 'Kazuto Takei',
                // Remplaçants (3)
                'Ryo Fujita', 'Kenta Wada', 'Yuji Morita',
            ],
            'Shimizu' => [
                'Morimichi Kawakami', 'Takeshi Kudo', 'Ichiro Kanda', 'Yuto Ibaraki',
                'Hiroshi Suzuki', 'Daisuke Takada', 'Ryota Nakao', 'Shinji Iimura',
                'Koji Murakami', 'Kazumasa Kato', 'Takashi Obayashi',
                // Remplaçants (3)
                'Naoto Kishi', 'Yosuke Murata', 'Kazuya Nishida',
            ],
            'Shimada' => [
                'Etsuo Nagai', 'Ikushi Ito', 'Koichi Fujisawa', 'Nemto Takahashi',
                'Jo Kimura', 'Koji Ishikawa', 'Takushi Hashimoto', 'Junichi Nagasaki',
                'Light Nakamura', 'Masayuki Jinbo', 'Naoki Wesugi',
                // Remplaçants (3)
                'Hiroshi Okamoto', 'Kenji Tsuda', 'Takuya Nozaki',
            ],
        ];
    }

    /**
     * Capitaine de chaque club (nom complet du joueur), indexé par nom de club.
     *
     * @return array<string, string>
     */
    public static function captains(): array
    {
        return [
            'Nankatsu' => 'Tsubasa Ozora',
            'Shutetsu' => 'Genzo Wakabayashi',
            'Toho'     => 'Kojiro Hyuga',
            'Furano'   => 'Hikaru Matsuyama',
            'Musashi'  => 'Jun Misugi',
            'Hanawa'   => 'Masao Tachibana',
            'Azumaichi'=> 'Makoto Soda',
            'Hirado'   => 'Hiroshi Jito',
            'Otomo'    => 'Hanji Urabe',
            'Meiwa'    => 'Noboru Sawaki',
            'Nakahara' => 'Shingo Aoi',
            'Naniwa'   => 'Taichi Nakanishi',
            'Minawi'   => 'Tetsuo Ishida',
            'Shimizu'  => 'Morimichi Kawakami',
            'Shimada'  => 'Masayuki Jinbo',
        ];
    }
}

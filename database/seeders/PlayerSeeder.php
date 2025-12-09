<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // On remet la table à zéro avant de reseed
        Schema::disableForeignKeyConstraints();
        DB::table('players')->truncate();
        Schema::enableForeignKeyConstraints();

        $players = [
            // Nankatsu
            ['Yuzo', 'Morisaki', 12, 'Goalkeeper', 680, ['speed' => 60, 'stamina' => 80, 'defense' => 85, 'attack' => 50]],
            ['Akira', 'Tsuboi', 12, 'Goalkeeper', 500, ['speed' => 40, 'stamina' => 60, 'defense' => 65, 'attack' => 35]],
            ['Ryo', 'Ishizaki', 12, 'Defender', 850, ['speed' => 65, 'stamina' => 75, 'defense' => 78, 'attack' => 55]],
            ['Masato', 'Nakazato', 12, 'Defender', 480, ['speed' => 45, 'stamina' => 55, 'defense' => 60, 'attack' => 40]],
            ['Hiroshi', 'Nagano', 12, 'Defender', 470, ['speed' => 43, 'stamina' => 58, 'defense' => 62, 'attack' => 38]],
            ['Manabu', 'Okawa', 12, 'Defender', 460, ['speed' => 44, 'stamina' => 57, 'defense' => 61, 'attack' => 37]],
            ['Susumu', 'Sakurai', 12, 'Defender', 450, ['speed' => 42, 'stamina' => 56, 'defense' => 60, 'attack' => 36]],
            ['Tsubasa', 'Ozora', 12, 'Midfielder', 1000, ['speed' => 78, 'stamina' => 85, 'defense' => 73, 'attack' => 89]],
            ['Taro', 'Misaki', 12, 'Midfielder', 950, ['speed' => 75, 'stamina' => 80, 'defense' => 68, 'attack' => 86]],
            ['Tsuyoshi', 'Oda', 12, 'Midfielder', 490, ['speed' => 50, 'stamina' => 60, 'defense' => 55, 'attack' => 45]],
            ['Kenichi', 'Iwami', 12, 'Midfielder', 480, ['speed' => 48, 'stamina' => 59, 'defense' => 54, 'attack' => 43]],
            ['Shota', 'Minowa', 12, 'Midfielder', 470, ['speed' => 46, 'stamina' => 57, 'defense' => 30, 'attack' => 68]],
            ['Yutaka', 'Murashige', 12, 'Forward', 500, ['speed' => 49, 'stamina' => 60, 'defense' => 42, 'attack' => 60]],

            // Shutetsu
            ['Genzo', 'Wakabayashi', 12, 'Goalkeeper', 1000, ['speed' => 70, 'stamina' => 70, 'defense' => 95, 'attack' => 40]],
            ['Shingo', 'Takasugi', 12, 'Defender', 750, ['speed' => 64, 'stamina' => 70, 'defense' => 70, 'attack' => 36]],
            ['Kenta', 'Shimada', 12, 'Defender', 500, ['speed' => 50, 'stamina' => 60, 'defense' => 65, 'attack' => 35]],
            ['Kazuma', 'Matsumo', 12, 'Defender', 450, ['speed' => 48, 'stamina' => 58, 'defense' => 60, 'attack' => 32]],
            ['Kohei', 'Nakamoto', 12, 'Defender', 480, ['speed' => 49, 'stamina' => 59, 'defense' => 62, 'attack' => 33]],
            ['Jun', 'Kurata', 12, 'Midfielder', 520, ['speed' => 52, 'stamina' => 63, 'defense' => 50, 'attack' => 48]],
            ['Takumi', 'Osaki', 12, 'Midfielder', 540, ['speed' => 54, 'stamina' => 65, 'defense' => 52, 'attack' => 49]],
            ['Kaito', 'Inamura', 12, 'Midfielder', 580, ['speed' => 55, 'stamina' => 67, 'defense' => 55, 'attack' => 51]],
            ['Mamoru', 'Izawa', 12, 'Midfielder', 900, ['speed' => 68, 'stamina' => 70, 'defense' => 65, 'attack' => 68]],
            ['Teppei', 'Kisugi', 13, 'Forward', 800, ['speed' => 70, 'stamina' => 80, 'defense' => 28, 'attack' => 73]],
            ['Hajime', 'Taki', 12, 'Forward', 800, ['speed' => 80, 'stamina' => 75, 'defense' => 40, 'attack' => 70]],

            // Toho
            ['Ken', 'Wakashimazu', 13, 'Goalkeeper', 930, ['speed' => 70, 'stamina' => 85, 'defense' => 90, 'attack' => 50]],
            ['Kiyoshi', 'Furuta', 12, 'Defender', 500, ['speed' => 60, 'stamina' => 68, 'defense' => 70, 'attack' => 45]],
            ['Katsuji', 'Kawabe', 12, 'Defender', 520, ['speed' => 62, 'stamina' => 70, 'defense' => 72, 'attack' => 47]],
            ['Tsuneo', 'Takashima', 12, 'Defender', 530, ['speed' => 64, 'stamina' => 72, 'defense' => 74, 'attack' => 49]],
            ['Hiroshi', 'Imai', 12, 'Defender', 510, ['speed' => 60, 'stamina' => 66, 'defense' => 68, 'attack' => 44]],
            ['Hideto', 'Koike', 12, 'Midfielder', 530, ['speed' => 62, 'stamina' => 70, 'defense' => 66, 'attack' => 47]],
            ['Yutaka', 'Matsuki', 12, 'Midfielder', 540, ['speed' => 64, 'stamina' => 72, 'defense' => 68, 'attack' => 50]],
            ['Takeshi', 'Sawada', 11, 'Midfielder', 830, ['speed' => 73, 'stamina' => 80, 'defense' => 60, 'attack' => 73]],
            ['Tadashi', 'Shimano', 12, 'Midfielder', 520, ['speed' => 63, 'stamina' => 70, 'defense' => 66, 'attack' => 48]],
            ['Kojiro', 'Hyuga', 13, 'Forward', 1000, ['speed' => 78, 'stamina' => 85, 'defense' => 65, 'attack' => 93]],
            ['Kazuki', 'Sorimachi', 12, 'Forward', 690, ['speed' => 68, 'stamina' => 75, 'defense' => 55, 'attack' => 70]],

            // Furano
            ['Masanori', 'Kato', 12, 'Goalkeeper', 500, ['speed' => 55, 'stamina' => 65, 'defense' => 75, 'attack' => 35]],
            ['Susumu', 'Honda', 12, 'Defender', 520, ['speed' => 60, 'stamina' => 70, 'defense' => 72, 'attack' => 45]],
            ['Tsuyoshi', 'Kondo', 12, 'Defender', 530, ['speed' => 58, 'stamina' => 68, 'defense' => 70, 'attack' => 42]],
            ['Kentaro', 'Kamata', 12, 'Defender', 510, ['speed' => 57, 'stamina' => 66, 'defense' => 69, 'attack' => 44]],
            ['Hisashi', 'Matsuda', 12, 'Midfielder', 540, ['speed' => 64, 'stamina' => 74, 'defense' => 68, 'attack' => 50]],
            ['Haruo', 'Kaneda', 12, 'Midfielder', 550, ['speed' => 56, 'stamina' => 65, 'defense' => 68, 'attack' => 43]],
            ['Hikaru', 'Matsuyama', 13, 'Midfielder', 950, ['speed' => 75, 'stamina' => 87, 'defense' => 80, 'attack' => 75]],
            ['Koichi', 'Wakamatsu', 12, 'Midfielder', 530, ['speed' => 55, 'stamina' => 64, 'defense' => 67, 'attack' => 42]],
            ['Seiji', 'Nakagawa', 12, 'Forward', 540, ['speed' => 60, 'stamina' => 68, 'defense' => 50, 'attack' => 60]],
            ['Kazumasa', 'Oda', 12, 'Forward', 630, ['speed' => 70, 'stamina' => 73, 'defense' => 60, 'attack' => 65]],
            ['Shuichi', 'Yamamuro', 12, 'Forward', 520, ['speed' => 60, 'stamina' => 67, 'defense' => 50, 'attack' => 60]],

            // Musashi
            ['Tsutomu', 'Moriyama', 12, 'Goalkeeper', 600, ['speed' => 50, 'stamina' => 70, 'defense' => 75, 'attack' => 40]],
            ['Osamu', 'Kido', 12, 'Defender', 580, ['speed' => 48, 'stamina' => 68, 'defense' => 70, 'attack' => 45]],
            ['Hiroshi', 'Mukai', 12, 'Defender', 560, ['speed' => 46, 'stamina' => 66, 'defense' => 68, 'attack' => 42]],
            ['Ryoichi', 'Sano', 12, 'Defender', 550, ['speed' => 45, 'stamina' => 65, 'defense' => 67, 'attack' => 41]],
            ['Shinichi', 'Suzuki', 12, 'Midfielder', 590, ['speed' => 49, 'stamina' => 69, 'defense' => 66, 'attack' => 48]],
            ['Kensaku', 'Yoshida', 12, 'Midfielder', 570, ['speed' => 47, 'stamina' => 67, 'defense' => 64, 'attack' => 47]],
            ['Shota', 'Inoue', 12, 'Midfielder', 580, ['speed' => 48, 'stamina' => 68, 'defense' => 64, 'attack' => 46]],
            ['Jun', 'Misugi', 13, 'Midfielder', 1000, ['speed' => 83, 'stamina' => 50, 'defense' => 90, 'attack' => 90]],
            ['Akira', 'Ichinose', 12, 'Forward', 700, ['speed' => 70, 'stamina' => 67, 'defense' => 40, 'attack' => 70]],
            ['Minoru', 'Honma', 12, 'Forward', 700, ['speed' => 65, 'stamina' => 75, 'defense' => 60, 'attack' => 67]],
            ['Shinji', 'Sanada', 12, 'Forward', 700, ['speed' => 65, 'stamina' => 72, 'defense' => 50, 'attack' => 65]],

            // Hanawa
            ['Kimio', 'Yoshikura', 12, 'Goalkeeper', 450, ['speed' => 55, 'stamina' => 65, 'defense' => 75, 'attack' => 35]],
            ['Masaru', 'Koda', 12, 'Defender', 470, ['speed' => 60, 'stamina' => 67, 'defense' => 70, 'attack' => 45]],
            ['Yusuaki', 'Murasawa', 12, 'Defender', 480, ['speed' => 58, 'stamina' => 68, 'defense' => 72, 'attack' => 42]],
            ['Norio', 'Nakamura', 12, 'Defender', 460, ['speed' => 57, 'stamina' => 66, 'defense' => 70, 'attack' => 44]],
            ['Yuichiro', 'Daimaru', 12, 'Midfielder', 620, ['speed' => 64, 'stamina' => 74, 'defense' => 68, 'attack' => 50]],
            ['Takayuki', 'Shiota', 12, 'Midfielder', 450, ['speed' => 56, 'stamina' => 65, 'defense' => 68, 'attack' => 43]],
            ['Nobuo', 'Aimoto', 12, 'Midfielder', 460, ['speed' => 55, 'stamina' => 64, 'defense' => 67, 'attack' => 42]],
            ['Hiroshi', 'Tamai', 12, 'Midfielder', 470, ['speed' => 59, 'stamina' => 67, 'defense' => 69, 'attack' => 44]],
            ['Masao', 'Tachibana', 13, 'Forward', 850, ['speed' => 75, 'stamina' => 75, 'defense' => 58, 'attack' => 80]],
            ['Kazuo', 'Tachibana', 13, 'Forward', 850, ['speed' => 75, 'stamina' => 75, 'defense' => 58, 'attack' => 80]],
            ['Yoshiharu', 'Ono', 12, 'Forward', 480, ['speed' => 60, 'stamina' => 68, 'defense' => 50, 'attack' => 50]],

            // Azuma-ichi
            ['Ryota', 'Tsuji', 12, 'Goalkeeper', 450, ['speed' => 55, 'stamina' => 65, 'defense' => 75, 'attack' => 35]],
            ['Makoto', 'Soda', 12, 'Defender', 860, ['speed' => 70, 'stamina' => 85, 'defense' => 82, 'attack' => 50]],
            ['Junji', 'Yamada', 12, 'Defender', 470, ['speed' => 60, 'stamina' => 67, 'defense' => 70, 'attack' => 45]],
            ['Daigo', 'Sasaki', 12, 'Defender', 480, ['speed' => 58, 'stamina' => 68, 'defense' => 72, 'attack' => 42]],
            ['Tatsuya', 'Hayashi', 12, 'Midfielder', 460, ['speed' => 57, 'stamina' => 66, 'defense' => 70, 'attack' => 44]],
            ['Koji', 'Yoshida', 12, 'Midfielder', 450, ['speed' => 56, 'stamina' => 65, 'defense' => 68, 'attack' => 43]],
            ['Yohei', 'Kuramochi', 12, 'Midfielder', 440, ['speed' => 55, 'stamina' => 64, 'defense' => 67, 'attack' => 42]],
            ['Toru', 'Nakai', 12, 'Midfielder', 470, ['speed' => 59, 'stamina' => 67, 'defense' => 69, 'attack' => 44]],
            ['Kazuyasu', 'Onodera', 12, 'Forward', 480, ['speed' => 60, 'stamina' => 68, 'defense' => 50, 'attack' => 50]],
            ['Mitsuru', 'Ide', 12, 'Forward', 460, ['speed' => 60, 'stamina' => 68, 'defense' => 50, 'attack' => 50]],
            ['Shohei', 'Mihashi', 12, 'Forward', 480, ['speed' => 60, 'stamina' => 68, 'defense' => 50, 'attack' => 50]],

            // Hirado
            ['Akira', 'Hatakeyama', 12, 'Goalkeeper', 450, ['speed' => 55, 'stamina' => 65, 'defense' => 75, 'attack' => 35]],
            ['Kazuaki', 'Soda', 12, 'Defender', 470, ['speed' => 60, 'stamina' => 67, 'defense' => 70, 'attack' => 45]],
            ['Hiroshi', 'Jito', 13, 'Defender', 880, ['speed' => 65, 'stamina' => 85, 'defense' => 85, 'attack' => 64]],
            ['Toshio', 'Akizawa', 12, 'Defender', 480, ['speed' => 58, 'stamina' => 68, 'defense' => 72, 'attack' => 42]],
            ['Shinji', 'Noda', 12, 'Midfielder', 460, ['speed' => 57, 'stamina' => 66, 'defense' => 70, 'attack' => 44]],
            ['Tsutomu', 'Nagaoka', 12, 'Midfielder', 450, ['speed' => 56, 'stamina' => 65, 'defense' => 68, 'attack' => 43]],
            ['Koji', 'Nakajo', 12, 'Midfielder', 440, ['speed' => 55, 'stamina' => 64, 'defense' => 67, 'attack' => 42]],
            ['Shinji', 'Morisue', 12, 'Midfielder', 470, ['speed' => 59, 'stamina' => 67, 'defense' => 69, 'attack' => 44]],
            ['Kazuo', 'Takeno', 12, 'Midfielder', 480, ['speed' => 58, 'stamina' => 66, 'defense' => 68, 'attack' => 45]],
            ['Katsumi', 'Himeji', 12, 'Forward', 460, ['speed' => 60, 'stamina' => 68, 'defense' => 50, 'attack' => 50]],
            ['Mitsuru', 'Sano', 13, 'Forward', 680, ['speed' => 62, 'stamina' => 76, 'defense' => 53, 'attack' => 81]],

            // Otomo
            ['Isamu', 'Ichijo', 12, 'Goalkeeper', 550, ['speed' => 50, 'stamina' => 65, 'defense' => 70, 'attack' => 40]],
            ['Masaki', 'Yoshikawa', 12, 'Defender', 570, ['speed' => 52, 'stamina' => 67, 'defense' => 72, 'attack' => 42]],
            ['Koji', 'Nishio', 12, 'Defender', 700, ['speed' => 55, 'stamina' => 70, 'defense' => 75, 'attack' => 45]],
            ['Masao', 'Nakayama', 12, 'Defender', 700, ['speed' => 55, 'stamina' => 70, 'defense' => 75, 'attack' => 45]],
            ['Kozo', 'Kawada', 12, 'Defender', 580, ['speed' => 53, 'stamina' => 68, 'defense' => 73, 'attack' => 43]],
            ['Toru', 'Hiraoka', 12, 'Midfielder', 560, ['speed' => 51, 'stamina' => 66, 'defense' => 65, 'attack' => 47]],
            ['Takeshi', 'Kishida', 12, 'Midfielder', 700, ['speed' => 55, 'stamina' => 70, 'defense' => 60, 'attack' => 50]],
            ['Hanji', 'Urabe', 12, 'Midfielder', 800, ['speed' => 60, 'stamina' => 75, 'defense' => 70, 'attack' => 55]],
            ['Shingo', 'Tadami', 12, 'Midfielder', 580, ['speed' => 53, 'stamina' => 68, 'defense' => 65, 'attack' => 50]],
            ['Akio', 'Nakao', 12, 'Forward', 600, ['speed' => 55, 'stamina' => 70, 'defense' => 40, 'attack' => 60]],
            ['Shun', 'Nitta', 12, 'Forward', 950, ['speed' => 70, 'stamina' => 75, 'defense' => 40, 'attack' => 75]],

            // Meiwa
            ['Tetsuji', 'Murasawa', 12, 'Goalkeeper', 450, ['speed' => 55, 'stamina' => 65, 'defense' => 75, 'attack' => 35]],
            ['Keiji', 'Kawagoe', 12, 'Defender', 460, ['speed' => 60, 'stamina' => 68, 'defense' => 70, 'attack' => 40]],
            ['Hiroshi', 'Ishii', 12, 'Defender', 470, ['speed' => 58, 'stamina' => 66, 'defense' => 72, 'attack' => 42]],
            ['Toshiyuki', 'Takagi', 12, 'Defender', 480, ['speed' => 57, 'stamina' => 67, 'defense' => 73, 'attack' => 41]],
            ['Motoharu', 'Nagano', 12, 'Defender', 650, ['speed' => 65, 'stamina' => 69, 'defense' => 75, 'attack' => 45]],
            ['Shinishi', 'Sakamoto', 12, 'Midfielder', 630, ['speed' => 64, 'stamina' => 70, 'defense' => 74, 'attack' => 60]],
            ['Kuniaki', 'Narita', 12, 'Midfielder', 620, ['speed' => 63, 'stamina' => 69, 'defense' => 73, 'attack' => 59]],
            ['Hiromichi', 'Hori', 12, 'Midfielder', 620, ['speed' => 63, 'stamina' => 69, 'defense' => 73, 'attack' => 59]],
            ['Kazushige', 'Enomoto', 12, 'Midfielder', 450, ['speed' => 62, 'stamina' => 64, 'defense' => 62, 'attack' => 57]],
            ['Noboru', 'Sawaki', 12, 'Forward', 600, ['speed' => 66, 'stamina' => 70, 'defense' => 60, 'attack' => 62]],
            ['Yuichi', 'Suenaga', 12, 'Forward', 640, ['speed' => 65, 'stamina' => 69, 'defense' => 59, 'attack' => 64]],

            // Nakahara
            ['Goro', 'Kawakami', 12, 'Goalkeeper', 500, ['speed' => 40, 'stamina' => 60, 'defense' => 65, 'attack' => 35]],
            ['Yuichi', 'Masumoto', 12, 'Midfielder', 480, ['speed' => 50, 'stamina' => 60, 'defense' => 55, 'attack' => 45]],
            ['Keisuke', 'Haranashi', 12, 'Defender', 460, ['speed' => 44, 'stamina' => 57, 'defense' => 61, 'attack' => 37]],
            ['Takamasa', 'Fujita', 12, 'Defender', 470, ['speed' => 43, 'stamina' => 58, 'defense' => 62, 'attack' => 38]],
            ['Jin', 'Toda', 12, 'Defender', 450, ['speed' => 42, 'stamina' => 56, 'defense' => 60, 'attack' => 36]],
            ['Ken', 'Nagatani', 12, 'Midfielder', 490, ['speed' => 50, 'stamina' => 60, 'defense' => 55, 'attack' => 45]],
            ['Shunta', 'Harukawa', 12, 'Midfielder', 480, ['speed' => 48, 'stamina' => 59, 'defense' => 54, 'attack' => 43]],
            ['Susumu', 'Itao', 12, 'Midfielder', 470, ['speed' => 46, 'stamina' => 57, 'defense' => 50, 'attack' => 48]],
            ['Goro', 'Kurita', 12, 'Midfielder', 460, ['speed' => 45, 'stamina' => 56, 'defense' => 51, 'attack' => 44]],
            ['Takeshi', 'Asada', 12, 'Forward', 500, ['speed' => 49, 'stamina' => 60, 'defense' => 52, 'attack' => 50]],
            ['Shingo', 'Aoi', 12, 'Forward', 900, ['speed' => 75, 'stamina' => 72, 'defense' => 40, 'attack' => 70]],

            // Naniwa
            ['Taichi', 'Nakanishi', 12, 'Goalkeeper', 800, ['speed' => 40, 'stamina' => 70, 'defense' => 85, 'attack' => 35]],
            ['Hiroshi', 'Tsusaki', 12, 'Defender', 480, ['speed' => 45, 'stamina' => 55, 'defense' => 60, 'attack' => 40]],
            ['Kazuya', 'Kosaka', 12, 'Defender', 470, ['speed' => 43, 'stamina' => 58, 'defense' => 62, 'attack' => 38]],
            ['Shinji', 'Yoshimoto', 12, 'Defender', 460, ['speed' => 44, 'stamina' => 57, 'defense' => 61, 'attack' => 37]],
            ['Daisuke', 'Tennoji', 12, 'Defender', 450, ['speed' => 42, 'stamina' => 56, 'defense' => 60, 'attack' => 36]],
            ['Masato', 'Dojima', 12, 'Midfielder', 490, ['speed' => 50, 'stamina' => 60, 'defense' => 55, 'attack' => 45]],
            ['Ryo', 'Maeda', 12, 'Midfielder', 480, ['speed' => 48, 'stamina' => 59, 'defense' => 54, 'attack' => 43]],
            ['Kenji', 'Shirai', 12, 'Midfielder', 470, ['speed' => 46, 'stamina' => 57, 'defense' => 50, 'attack' => 48]],
            ['Yuta', 'Ogami', 12, 'Midfielder', 460, ['speed' => 45, 'stamina' => 56, 'defense' => 51, 'attack' => 44]],
            ['Satoshi', 'Takayanagi', 12, 'Forward', 500, ['speed' => 49, 'stamina' => 60, 'defense' => 52, 'attack' => 50]],
            ['Tetsuya', 'Marui', 12, 'Forward', 480, ['speed' => 48, 'stamina' => 58, 'defense' => 51, 'attack' => 47]],

            // Minawi
            ['Hajime', 'Asakura', 12, 'Goalkeeper', 450, ['speed' => 55, 'stamina' => 65, 'defense' => 75, 'attack' => 35]],
            ['Daichi', 'Azuma', 12, 'Defender', 460, ['speed' => 60, 'stamina' => 68, 'defense' => 70, 'attack' => 40]],
            ['Shinji', 'Takahama', 12, 'Defender', 470, ['speed' => 58, 'stamina' => 66, 'defense' => 72, 'attack' => 42]],
            ['Ryu', 'Kawanoe', 12, 'Defender', 480, ['speed' => 57, 'stamina' => 67, 'defense' => 73, 'attack' => 41]],
            ['Takashi', 'Iyo', 12, 'Defender', 490, ['speed' => 56, 'stamina' => 69, 'defense' => 71, 'attack' => 43]],
            ['Koji', 'Tosa', 12, 'Midfielder', 420, ['speed' => 60, 'stamina' => 62, 'defense' => 60, 'attack' => 55]],
            ['Hiroto', 'Shintani', 12, 'Midfielder', 430, ['speed' => 61, 'stamina' => 63, 'defense' => 61, 'attack' => 56]],
            ['Tetsuo', 'Ishida', 12, 'Midfielder', 650, ['speed' => 69, 'stamina' => 74, 'defense' => 63, 'attack' => 70]],
            ['Masaru', 'Hirayama', 12, 'Midfielder', 440, ['speed' => 62, 'stamina' => 64, 'defense' => 62, 'attack' => 57]],
            ['Kazuki', 'Seto', 12, 'Forward', 470, ['speed' => 63, 'stamina' => 65, 'defense' => 58, 'attack' => 60]],
            ['Kazuto', 'Takei', 12, 'Forward', 500, ['speed' => 64, 'stamina' => 66, 'defense' => 59, 'attack' => 61]],

            // Shimizu
            ['Morimichi', 'Kawakami', 12, 'Goalkeeper', 600, ['speed' => 40, 'stamina' => 65, 'defense' => 65, 'attack' => 35]],
            ['Takeshi', 'Kudo', 12, 'Defender', 480, ['speed' => 45, 'stamina' => 55, 'defense' => 60, 'attack' => 40]],
            ['Ichiro', 'Kanda', 12, 'Defender', 470, ['speed' => 43, 'stamina' => 58, 'defense' => 62, 'attack' => 38]],
            ['Yuto', 'Ibaraki', 12, 'Defender', 460, ['speed' => 44, 'stamina' => 57, 'defense' => 61, 'attack' => 37]],
            ['Hiroshi', 'Suzuki', 12, 'Defender', 450, ['speed' => 42, 'stamina' => 56, 'defense' => 60, 'attack' => 36]],
            ['Daisuke', 'Takada', 12, 'Midfielder', 490, ['speed' => 50, 'stamina' => 60, 'defense' => 55, 'attack' => 45]],
            ['Ryota', 'Nakao', 12, 'Midfielder', 480, ['speed' => 48, 'stamina' => 59, 'defense' => 54, 'attack' => 43]],
            ['Shinji', 'Iimura', 12, 'Midfielder', 470, ['speed' => 46, 'stamina' => 57, 'defense' => 50, 'attack' => 48]],
            ['Koji', 'Murakami', 12, 'Midfielder', 460, ['speed' => 45, 'stamina' => 56, 'defense' => 51, 'attack' => 44]],
            ['Kazumasa', 'Kato', 12, 'Forward', 500, ['speed' => 49, 'stamina' => 60, 'defense' => 52, 'attack' => 50]],
            ['Takashi', 'Obayashi', 12, 'Forward', 480, ['speed' => 48, 'stamina' => 58, 'defense' => 51, 'attack' => 47]],

            // Shimada
            ['Etsuo', 'Nagai', 12, 'Goalkeeper', 500, ['speed' => 40, 'stamina' => 60, 'defense' => 65, 'attack' => 35]],
            ['Ikushi', 'Ito', 12, 'Defender', 480, ['speed' => 45, 'stamina' => 55, 'defense' => 60, 'attack' => 40]],
            ['Koichi', 'Fujisawa', 12, 'Defender', 470, ['speed' => 43, 'stamina' => 58, 'defense' => 62, 'attack' => 38]],
            ['Nemto', 'Takahashi', 12, 'Defender', 460, ['speed' => 44, 'stamina' => 57, 'defense' => 61, 'attack' => 37]],
            ['Jo', 'Kimura', 12, 'Midfielder', 490, ['speed' => 50, 'stamina' => 60, 'defense' => 55, 'attack' => 45]],
            ['Koji', 'Ishikawa', 12, 'Midfielder', 480, ['speed' => 48, 'stamina' => 59, 'defense' => 54, 'attack' => 43]],
            ['Takushi', 'Hashimoto', 12, 'Forward', 470, ['speed' => 46, 'stamina' => 57, 'defense' => 50, 'attack' => 48]],
            ['Junichi', 'Nagasaki', 12, 'Forward', 500, ['speed' => 59, 'stamina' => 60, 'defense' => 52, 'attack' => 50]],
            ['Light', 'Nakamura', 12, 'Midfielder', 480, ['speed' => 47, 'stamina' => 58, 'defense' => 53, 'attack' => 44]],
            ['Masayuki', 'Jinbo', 12, 'Midfielder', 490, ['speed' => 50, 'stamina' => 59, 'defense' => 55, 'attack' => 45]],
            ['Naoki', 'Wesugi', 12, 'Forward', 500, ['speed' => 50, 'stamina' => 60, 'defense' => 50, 'attack' => 49]],

            // Real Seven Not contract
            ['Michel', 'Yamada', 13, 'Goalkeeper', 600, ['speed' => 60, 'stamina' => 70, 'defense' => 75, 'attack' => 40]],
            ['Yuji', 'Sakaki', 13, 'Defender', 570, ['speed' => 58, 'stamina' => 67, 'defense' => 72, 'attack' => 45]],
            ['Yuji', 'Soga', 13, 'Defender', 580, ['speed' => 60, 'stamina' => 68, 'defense' => 73, 'attack' => 46]],
            ['Gakuto', 'Igawa', 13, 'Midfielder', 590, ['speed' => 62, 'stamina' => 68, 'defense' => 70, 'attack' => 50]],
            ['Kotaru', 'Furukawa', 13, 'Midfielder', 600, ['speed' => 63, 'stamina' => 69, 'defense' => 71, 'attack' => 51]],
            ['Takashi', 'Sugimoto', 13, 'Midfielder', 610, ['speed' => 65, 'stamina' => 70, 'defense' => 72, 'attack' => 53]],
            ['Nobuyuki', 'Yumikura', 13, 'Midfielder', 620, ['speed' => 66, 'stamina' => 71, 'defense' => 73, 'attack' => 54]],
            ['Toshiya', 'Okano', 13, 'Midfielder', 600, ['speed' => 64, 'stamina' => 69, 'defense' => 70, 'attack' => 52]],
            ['Shinnosuke', 'Kazami', 13, 'Forward', 630, ['speed' => 67, 'stamina' => 70, 'defense' => 65, 'attack' => 75]],
            ['Ryoma', 'Hino', 12, 'Forward', 890, ['speed' => 70, 'stamina' => 80, 'defense' => 55, 'attack' => 88]],

            // Other not contract
            ['Masaru', 'Ito', 12, 'Midfielder', 420, ['speed' => 50, 'stamina' => 55, 'defense' => 45, 'attack' => 50]],
            ['Takeshi', 'Kira', 12, 'Forward', 350, ['speed' => 45, 'stamina' => 50, 'defense' => 40, 'attack' => 45]],
            ['Daichi', 'Kakeru', 12, 'Midfielder', 420, ['speed' => 52, 'stamina' => 57, 'defense' => 47, 'attack' => 52]],
            ['Yayoi', 'Aoba', 12, 'Defender', 410, ['speed' => 48, 'stamina' => 54, 'defense' => 58, 'attack' => 44]],
            ['Kuniharu', 'Uematsu', 12, 'Defender', 400, ['speed' => 47, 'stamina' => 52, 'defense' => 57, 'attack' => 43]],
            ['Katsutoshi', 'Hasegawa', 13, 'Midfielder', 420, ['speed' => 48, 'stamina' => 55, 'defense' => 49, 'attack' => 50]],
            ['Sho', 'Kazama', 12, 'Defender', 420, ['speed' => 50, 'stamina' => 55, 'defense' => 60, 'attack' => 45]],
            ['Masaki', 'Kozou', 13, 'Forward', 420, ['speed' => 53, 'stamina' => 55, 'defense' => 42, 'attack' => 56]],
            ['Takuya', 'Furano', 12, 'Midfielder', 400, ['speed' => 48, 'stamina' => 52, 'defense' => 47, 'attack' => 48]],
            ['Kazuya', 'Kano', 12, 'Forward', 410, ['speed' => 49, 'stamina' => 53, 'defense' => 45, 'attack' => 49]],
            ['Haruto', 'Kobayashi', 12, 'Midfielder', 360, ['speed' => 45, 'stamina' => 50, 'defense' => 40, 'attack' => 47]],
            ['Riku', 'Yamamoto', 12, 'Defender', 350, ['speed' => 42, 'stamina' => 48, 'defense' => 50, 'attack' => 40]],
            ['Yuto', 'Tanaka', 12, 'Forward', 340, ['speed' => 50, 'stamina' => 45, 'defense' => 35, 'attack' => 52]],
            ['Sota', 'Saito', 12, 'Goalkeeper', 330, ['speed' => 38, 'stamina' => 47, 'defense' => 55, 'attack' => 30]],
            ['Daiki', 'Nishimura', 12, 'Midfielder', 320, ['speed' => 43, 'stamina' => 46, 'defense' => 40, 'attack' => 45]],
            ['Kaito', 'Fujimoto', 12, 'Defender', 310, ['speed' => 40, 'stamina' => 45, 'defense' => 48, 'attack' => 37]],
            ['Hinata', 'Kimura', 12, 'Forward', 300, ['speed' => 48, 'stamina' => 43, 'defense' => 33, 'attack' => 50]],
            ['Ren', 'Shimizu', 12, 'Midfielder', 290, ['speed' => 42, 'stamina' => 42, 'defense' => 38, 'attack' => 43]],
            ['Koki', 'Hayashi', 12, 'Defender', 280, ['speed' => 39, 'stamina' => 40, 'defense' => 45, 'attack' => 35]],
            ['Yuma', 'Ishikawa', 12, 'Goalkeeper', 270, ['speed' => 36, 'stamina' => 42, 'defense' => 50, 'attack' => 28]],
            ['Shion', 'Matsui', 12, 'Midfielder', 260, ['speed' => 40, 'stamina' => 40, 'defense' => 36, 'attack' => 41]],
            ['Keita', 'Inoue', 12, 'Defender', 250, ['speed' => 38, 'stamina' => 38, 'defense' => 43, 'attack' => 34]],
            ['Takumi', 'Yamada', 12, 'Forward', 240, ['speed' => 46, 'stamina' => 37, 'defense' => 31, 'attack' => 48]],
            ['Ryota', 'Kondo', 12, 'Midfielder', 230, ['speed' => 39, 'stamina' => 35, 'defense' => 34, 'attack' => 39]],
            ['Sho', 'Suzuki', 12, 'Defender', 220, ['speed' => 35, 'stamina' => 36, 'defense' => 40, 'attack' => 32]],
            ['Kensuke', 'Hara', 12, 'Goalkeeper', 210, ['speed' => 33, 'stamina' => 35, 'defense' => 48, 'attack' => 26]],
            ['Aoi', 'Ogawa', 12, 'Midfielder', 200, ['speed' => 38, 'stamina' => 34, 'defense' => 32, 'attack' => 37]],
            ['Haruki', 'Mori', 12, 'Midfielder', 200, ['speed' => 32, 'stamina' => 34, 'defense' => 28, 'attack' => 35]],
            ['Kazuya', 'Kobayashi', 12, 'Defender', 190, ['speed' => 30, 'stamina' => 32, 'defense' => 35, 'attack' => 25]],
            ['Takashi', 'Yamada', 12, 'Forward', 180, ['speed' => 35, 'stamina' => 30, 'defense' => 20, 'attack' => 37]],
            ['Satoshi', 'Suzuki', 12, 'Goalkeeper', 170, ['speed' => 28, 'stamina' => 31, 'defense' => 40, 'attack' => 20]],
            ['Ryosuke', 'Tanaka', 12, 'Midfielder', 160, ['speed' => 31, 'stamina' => 30, 'defense' => 27, 'attack' => 32]],
            ['Naoki', 'Fujimoto', 12, 'Defender', 150, ['speed' => 28, 'stamina' => 28, 'defense' => 32, 'attack' => 23]],
            ['Yuki', 'Kato', 12, 'Forward', 140, ['speed' => 33, 'stamina' => 27, 'defense' => 18, 'attack' => 35]],
            ['Koji', 'Nakamura', 12, 'Midfielder', 130, ['speed' => 30, 'stamina' => 26, 'defense' => 25, 'attack' => 30]],
            ['Shun', 'Sato', 12, 'Defender', 120, ['speed' => 27, 'stamina' => 25, 'defense' => 30, 'attack' => 20]],
            ['Takeshi', 'Matsumoto', 12, 'Goalkeeper', 110, ['speed' => 25, 'stamina' => 26, 'defense' => 35, 'attack' => 15]],
            ['Hiroto', 'Watanabe', 12, 'Midfielder', 100, ['speed' => 29, 'stamina' => 24, 'defense' => 23, 'attack' => 28]],
            ['Kenta', 'Kimura', 12, 'Defender', 200, ['speed' => 26, 'stamina' => 22, 'defense' => 28, 'attack' => 18]],
            ['Riku', 'Ito', 12, 'Forward', 190, ['speed' => 32, 'stamina' => 21, 'defense' => 15, 'attack' => 33]],
            ['Yuta', 'Sakai', 12, 'Midfielder', 180, ['speed' => 28, 'stamina' => 20, 'defense' => 22, 'attack' => 26]],
            ['Daichi', 'Nakajima', 12, 'Defender', 170, ['speed' => 25, 'stamina' => 18, 'defense' => 25, 'attack' => 15]],
            ['Koki', 'Yoshida', 12, 'Goalkeeper', 160, ['speed' => 23, 'stamina' => 19, 'defense' => 32, 'attack' => 10]],
            ['Shota', 'Harada', 12, 'Midfielder', 150, ['speed' => 27, 'stamina' => 18, 'defense' => 21, 'attack' => 25]],
            ['Takuya', 'Hasegawa', 12, 'Defender', 140, ['speed' => 24, 'stamina' => 16, 'defense' => 26, 'attack' => 14]],
            ['Ren', 'Yamashita', 12, 'Forward', 130, ['speed' => 30, 'stamina' => 15, 'defense' => 13, 'attack' => 32]],
            ['Haru', 'Ogawa', 12, 'Midfielder', 120, ['speed' => 26, 'stamina' => 14, 'defense' => 20, 'attack' => 23]],
        ];

        foreach ($players as $player) {
            $baseStats = $player[5];      // speed, stamina, defense, attack
            $position  = $player[3];      // Forward / Midfielder / Defender / Goalkeeper

            $fullStats = $this->buildSkills($baseStats, $position);

            DB::table('players')->insert([
                'firstname' => $player[0],
                'lastname'  => $player[1],
                'age'       => $player[2],
                'position'  => $position,
                'cost'      => $player[4],
                'stats'     => json_encode($fullStats),
            ]);
        }
    }

    /**
     * Génère les stats détaillées à partir des stats de base + position.
     */
    private function buildSkills(array $baseStats, string $position): array
    {
        $speed   = $baseStats['speed'];
        $stamina = $baseStats['stamina'];
        $defense = $baseStats['defense'];
        $attack  = $baseStats['attack'];

        // valeurs "génériques"
        $shot      = (int) round($attack);
        $pass      = (int) round($attack * 0.7 + $speed * 0.3);
        $dribble   = (int) round($attack * 0.7 + $speed * 0.3);
        $block     = (int) round($defense * 0.8 + $stamina * 0.2);
        $intercept = (int) round($defense * 0.7 + $speed * 0.3);
        $tackle    = (int) round($defense * 0.9 + $stamina * 0.1);

        // par défaut, les joueurs de champ ne sont pas bons gardiens
        $handSave  = max(5, (int) round($defense * 0.2));
        $punchSave = max(5, (int) round($defense * 0.15));

        switch ($position) {
            case 'Forward':
                $shot    = (int) round(min(100, $attack * 1.05));
                $dribble = (int) round(min(100, ($attack * 0.8 + $speed * 0.4) / 1.1));
                $pass    = (int) round(($attack * 0.75 + $speed * 0.35) / 1.1);
                break;

            case 'Midfielder':
                $pass    = (int) round(min(100, ($attack * 0.9 + $speed * 0.4) / 1.1));
                $dribble = (int) round(min(100, ($attack * 0.85 + $speed * 0.4) / 1.1));
                break;

            case 'Defender':
                $block     = (int) round(min(100, $block * 1.05));
                $tackle    = (int) round(min(100, $tackle * 1.05));
                $intercept = (int) round(($defense * 0.8 + $speed * 0.3) / 1.1);
                break;

            case 'Goalkeeper':
                // les GK sont spéciaux
                $shot    = (int) round($attack * 0.8);
                $pass    = (int) round($attack * 0.7 + $speed * 0.3);
                $dribble = (int) round($speed * 0.7 + $defense * 0.3);

                $block     = (int) round(($defense * 0.9 + $stamina * 0.3) / 1.2);
                $intercept = (int) round($defense * 0.8 + $speed * 0.2);
                $tackle    = (int) round($defense * 0.7 + $stamina * 0.3);

                $handSave  = (int) round(min(100, ($defense * 1.3 + $stamina * 0.5) / 1.5));
                $punchSave = (int) round(min(100, ($defense * 1.1 + $stamina * 0.7) / 1.5));
                break;
        }

        return $baseStats + [
                'shot'       => $shot,
                'pass'       => $pass,
                'dribble'    => $dribble,
                'block'      => $block,
                'intercept'  => $intercept,
                'tackle'     => $tackle,
                'hand_save'  => $handSave,
                'punch_save' => $punchSave,
            ];
    }
}

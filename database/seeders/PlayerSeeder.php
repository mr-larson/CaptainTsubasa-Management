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
            ['Yuzo', 'Morisaki', 12, 'Goalkeeper', 340, [
                'speed' => 60, 'stamina' => 80, 'defense' => 32, 'attack' => 18, 'shot' => 16, 'pass' => 18, 'dribble' => 16, 'block' => 18, 'intercept' => 20, 'tackle' => 18, 'hand_save' => 27, 'punch_save' => 24
            ]],
            ['Masato', 'Nakazato', 12, 'Defender', 240, [
                'speed' => 45, 'stamina' => 55, 'defense' => 22, 'attack' => 18, 'shot' => 16, 'pass' => 17, 'dribble' => 16, 'block' => 20, 'intercept' => 18, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ryo', 'Ishizaki', 12, 'Defender', 425, [
                'speed' => 65, 'stamina' => 75, 'defense' => 31, 'attack' => 22, 'shot' => 18, 'pass' => 23, 'dribble' => 18, 'block' => 25, 'intercept' => 24, 'tackle' => 27, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hiroshi', 'Nagano', 12, 'Defender', 235, [
                'speed' => 43, 'stamina' => 58, 'defense' => 23, 'attack' => 17, 'shot' => 16, 'pass' => 17, 'dribble' => 16, 'block' => 21, 'intercept' => 19, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Manabu', 'Okawa', 12, 'Defender', 230, [
                'speed' => 44, 'stamina' => 57, 'defense' => 22, 'attack' => 17, 'shot' => 16, 'pass' => 17, 'dribble' => 16, 'block' => 20, 'intercept' => 19, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Susumu', 'Sakurai', 12, 'Defender', 225, [
                'speed' => 42, 'stamina' => 56, 'defense' => 21, 'attack' => 16, 'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 20, 'intercept' => 18, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Taro', 'Misaki', 12, 'Midfielder', 475, [
                'speed' => 75, 'stamina' => 80, 'defense' => 26, 'attack' => 37, 'shot' => 28, 'pass' => 30, 'dribble' => 28, 'block' => 19, 'intercept' => 23, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Tsubasa', 'Ozora', 12, 'Midfielder', 500, [
                'speed' => 78, 'stamina' => 85, 'defense' => 30, 'attack' => 39, 'shot' => 30, 'pass' => 29, 'dribble' => 30, 'block' => 20, 'intercept' => 22, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Tsuyoshi', 'Oda', 12, 'Midfielder', 245, [
                'speed' => 50, 'stamina' => 60, 'defense' => 20, 'attack' => 22, 'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 18, 'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kenichi', 'Iwami', 12, 'Midfielder', 240, [
                'speed' => 48, 'stamina' => 59, 'defense' => 19, 'attack' => 21, 'shot' => 19, 'pass' => 21, 'dribble' => 19, 'block' => 18, 'intercept' => 18, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yutaka', 'Murashige', 12, 'Forward', 250, [
                'speed' => 49, 'stamina' => 60, 'defense' => 16, 'attack' => 28, 'shot' => 25, 'pass' => 20, 'dribble' => 22, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shota', 'Minowa', 12, 'Midfielder', 235, [
                'speed' => 46, 'stamina' => 57, 'defense' => 18, 'attack' => 30, 'shot' => 24, 'pass' => 22, 'dribble' => 23, 'block' => 17, 'intercept' => 18, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Akira', 'Tsuboi', 12, 'Goalkeeper', 250, [
                'speed' => 40, 'stamina' => 60, 'defense' => 24, 'attack' => 16, 'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 15, 'hand_save' => 21, 'punch_save' => 18
            ]],

            // Shutetsu
            ['Genzo', 'Wakabayashi', 12, 'Goalkeeper', 500, [
                'speed' => 70, 'stamina' => 70, 'defense' => 38, 'attack' => 18, 'shot' => 16, 'pass' => 18, 'dribble' => 16, 'block' => 22, 'intercept' => 24, 'tackle' => 18, 'hand_save' => 30, 'punch_save' => 28
            ]],
            ['Kenta', 'Shimada', 12, 'Defender', 250, [
                'speed' => 50, 'stamina' => 60, 'defense' => 26, 'attack' => 18, 'shot' => 16, 'pass' => 17, 'dribble' => 16, 'block' => 22, 'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shingo', 'Takasugi', 12, 'Defender', 375, [
                'speed' => 64, 'stamina' => 70, 'defense' => 30, 'attack' => 20, 'shot' => 18, 'pass' => 20, 'dribble' => 17, 'block' => 25, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazuma', 'Matsumo', 12, 'Defender', 225, [
                'speed' => 48, 'stamina' => 58, 'defense' => 24, 'attack' => 17, 'shot' => 16, 'pass' => 16, 'dribble' => 15, 'block' => 21, 'intercept' => 19, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kohei', 'Nakamoto', 12, 'Defender', 240, [
                'speed' => 49, 'stamina' => 59, 'defense' => 25, 'attack' => 17, 'shot' => 16, 'pass' => 17, 'dribble' => 16, 'block' => 22, 'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Jun', 'Kurata', 12, 'Midfielder', 260, [
                'speed' => 52, 'stamina' => 63, 'defense' => 21, 'attack' => 23, 'shot' => 20, 'pass' => 22, 'dribble' => 21, 'block' => 18, 'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takumi', 'Osaki', 12, 'Midfielder', 270, [
                'speed' => 54, 'stamina' => 65, 'defense' => 22, 'attack' => 24, 'shot' => 21, 'pass' => 23, 'dribble' => 22, 'block' => 18, 'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Mamoru', 'Izawa', 12, 'Midfielder', 450, [
                'speed' => 68, 'stamina' => 70, 'defense' => 28, 'attack' => 32, 'shot' => 25, 'pass' => 27, 'dribble' => 24, 'block' => 20, 'intercept' => 23, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kaito', 'Inamura', 12, 'Midfielder', 290, [
                'speed' => 55, 'stamina' => 67, 'defense' => 23, 'attack' => 25, 'shot' => 22, 'pass' => 24, 'dribble' => 23, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Teppei', 'Kisugi', 13, 'Forward', 400, [
                'speed' => 70, 'stamina' => 80, 'defense' => 16, 'attack' => 34, 'shot' => 28, 'pass' => 21, 'dribble' => 24, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hajime', 'Taki', 12, 'Forward', 400, [
                'speed' => 80, 'stamina' => 75, 'defense' => 18, 'attack' => 33, 'shot' => 27, 'pass' => 22, 'dribble' => 25, 'block' => 16, 'intercept' => 17, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Toho
            ['Ken', 'Wakashimazu', 13, 'Goalkeeper', 465, [
                'speed' => 70, 'stamina' => 85, 'defense' => 36, 'attack' => 22, 'shot' => 22, 'pass' => 20, 'dribble' => 19, 'block' => 22, 'intercept' => 24, 'tackle' => 21, 'hand_save' => 28, 'punch_save' => 29
            ]],
            ['Kiyoshi', 'Furuta', 12, 'Defender', 250, [
                'speed' => 60, 'stamina' => 68, 'defense' => 28, 'attack' => 21, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 23, 'intercept' => 22, 'tackle' => 24, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Katsuji', 'Kawabe', 12, 'Defender', 260, [
                'speed' => 62, 'stamina' => 70, 'defense' => 29, 'attack' => 22, 'shot' => 18, 'pass' => 20, 'dribble' => 18, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Tsuneo', 'Takashima', 12, 'Defender', 265, [
                'speed' => 64, 'stamina' => 72, 'defense' => 30, 'attack' => 23, 'shot' => 19, 'pass' => 20, 'dribble' => 18, 'block' => 25, 'intercept' => 24, 'tackle' => 26, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hiroshi', 'Imai', 12, 'Defender', 255, [
                'speed' => 60, 'stamina' => 66, 'defense' => 27, 'attack' => 21, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 23, 'intercept' => 22, 'tackle' => 24, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hideto', 'Koike', 12, 'Midfielder', 265, [
                'speed' => 62, 'stamina' => 70, 'defense' => 26, 'attack' => 22, 'shot' => 21, 'pass' => 23, 'dribble' => 21, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yutaka', 'Matsuki', 12, 'Midfielder', 270, [
                'speed' => 64, 'stamina' => 72, 'defense' => 27, 'attack' => 24, 'shot' => 22, 'pass' => 24, 'dribble' => 22, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takeshi', 'Sawada', 11, 'Midfielder', 415, [
                'speed' => 73, 'stamina' => 80, 'defense' => 28, 'attack' => 35, 'shot' => 25, 'pass' => 27, 'dribble' => 27, 'block' => 19, 'intercept' => 24, 'tackle' => 23, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Tadashi', 'Shimano', 12, 'Midfielder', 260, [
                'speed' => 63, 'stamina' => 70, 'defense' => 26, 'attack' => 23, 'shot' => 21, 'pass' => 23, 'dribble' => 21, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kojiro', 'Hyuga', 13, 'Forward', 500, [
                'speed' => 78, 'stamina' => 85, 'defense' => 22, 'attack' => 42, 'shot' => 31, 'pass' => 20, 'dribble' => 27, 'block' => 18, 'intercept' => 20, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazuki', 'Sorimachi', 12, 'Forward', 345, [
                'speed' => 68, 'stamina' => 75, 'defense' => 20, 'attack' => 32, 'shot' => 26, 'pass' => 23, 'dribble' => 24, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Furano
            ['Masanori', 'Kato', 12, 'Goalkeeper', 250, [
                'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16, 'shot' => 15, 'pass' => 18, 'dribble' => 15, 'block' => 20, 'intercept' => 21, 'tackle' => 17, 'hand_save' => 25, 'punch_save' => 22
            ]],
            ['Susumu', 'Honda', 12, 'Defender', 260, [
                'speed' => 60, 'stamina' => 70, 'defense' => 29, 'attack' => 22, 'shot' => 18, 'pass' => 20, 'dribble' => 18, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Tsuyoshi', 'Kondo', 12, 'Defender', 265, [
                'speed' => 58, 'stamina' => 68, 'defense' => 28, 'attack' => 21, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 23, 'intercept' => 22, 'tackle' => 24, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kentaro', 'Kamata', 12, 'Defender', 255, [
                'speed' => 57, 'stamina' => 66, 'defense' => 27, 'attack' => 22, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 23, 'intercept' => 22, 'tackle' => 24, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hisashi', 'Matsuda', 12, 'Midfielder', 270, [
                'speed' => 64, 'stamina' => 74, 'defense' => 27, 'attack' => 24, 'shot' => 22, 'pass' => 24, 'dribble' => 22, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Haruo', 'Kaneda', 12, 'Midfielder', 275, [
                'speed' => 56, 'stamina' => 65, 'defense' => 26, 'attack' => 21, 'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koichi', 'Wakamatsu', 12, 'Midfielder', 265, [
                'speed' => 55, 'stamina' => 64, 'defense' => 26, 'attack' => 21, 'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hikaru', 'Matsuyama', 13, 'Midfielder', 475, [
                'speed' => 75, 'stamina' => 87, 'defense' => 35, 'attack' => 36, 'shot' => 28, 'pass' => 25, 'dribble' => 25, 'block' => 25, 'intercept' => 26, 'tackle' => 26, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Seiji', 'Nakagawa', 12, 'Forward', 270, [
                'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 30, 'shot' => 25, 'pass' => 20, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazumasa', 'Oda', 12, 'Forward', 315, [
                'speed' => 70, 'stamina' => 73, 'defense' => 20, 'attack' => 32, 'shot' => 27, 'pass' => 21, 'dribble' => 24, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shuichi', 'Yamamuro', 12, 'Forward', 260, [
                'speed' => 60, 'stamina' => 67, 'defense' => 18, 'attack' => 30, 'shot' => 25, 'pass' => 20, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Musashi
            ['Tsutomu', 'Moriyama', 12, 'Goalkeeper', 300, [
                'speed' => 50, 'stamina' => 70, 'defense' => 30, 'attack' => 18, 'shot' => 16, 'pass' => 18, 'dribble' => 15, 'block' => 22, 'intercept' => 22, 'tackle' => 18, 'hand_save' => 26, 'punch_save' => 23
            ]],
            ['Osamu', 'Kido', 12, 'Defender', 290, [
                'speed' => 48, 'stamina' => 68, 'defense' => 29, 'attack' => 22, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hiroshi', 'Mukai', 12, 'Defender', 280, [
                'speed' => 46, 'stamina' => 66, 'defense' => 28, 'attack' => 21, 'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 23, 'intercept' => 22, 'tackle' => 24, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ryoichi', 'Sano', 12, 'Defender', 275, [
                'speed' => 45, 'stamina' => 65, 'defense' => 27, 'attack' => 21, 'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 23, 'intercept' => 22, 'tackle' => 23, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shinichi', 'Suzuki', 12, 'Midfielder', 295, [
                'speed' => 49, 'stamina' => 69, 'defense' => 26, 'attack' => 24, 'shot' => 22, 'pass' => 24, 'dribble' => 22, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kensaku', 'Yoshida', 12, 'Midfielder', 285, [
                'speed' => 47, 'stamina' => 67, 'defense' => 25, 'attack' => 23, 'shot' => 21, 'pass' => 23, 'dribble' => 21, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shota', 'Inoue', 12, 'Midfielder', 290, [
                'speed' => 48, 'stamina' => 68, 'defense' => 25, 'attack' => 23, 'shot' => 21, 'pass' => 23, 'dribble' => 21, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Jun', 'Misugi', 13, 'Midfielder', 500, [
                'speed' => 83, 'stamina' => 50, 'defense' => 38, 'attack' => 38, 'shot' => 30, 'pass' => 29, 'dribble' => 29, 'block' => 25, 'intercept' => 26, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Minoru', 'Honma', 12, 'Forward', 350, [
                'speed' => 65, 'stamina' => 75, 'defense' => 20, 'attack' => 31, 'shot' => 26, 'pass' => 22, 'dribble' => 23, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Akira', 'Ichinose', 12, 'Forward', 350, [
                'speed' => 70, 'stamina' => 67, 'defense' => 18, 'attack' => 32, 'shot' => 27, 'pass' => 21, 'dribble' => 24, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shinji', 'Sanada', 12, 'Forward', 350, [
                'speed' => 65, 'stamina' => 72, 'defense' => 19, 'attack' => 30, 'shot' => 25, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Hanawa
            ['Kimio', 'Yoshikura', 12, 'Goalkeeper', 225, [
                'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16, 'shot' => 15, 'pass' => 18, 'dribble' => 15, 'block' => 21, 'intercept' => 22, 'tackle' => 18, 'hand_save' => 25, 'punch_save' => 22
            ]],
            ['Masaru', 'Koda', 12, 'Defender', 235, [
                'speed' => 60, 'stamina' => 67, 'defense' => 28, 'attack' => 22, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yusuaki', 'Murasawa', 12, 'Defender', 240, [
                'speed' => 58, 'stamina' => 68, 'defense' => 29, 'attack' => 21, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Norio', 'Nakamura', 12, 'Defender', 230, [
                'speed' => 57, 'stamina' => 66, 'defense' => 28, 'attack' => 22, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 24, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yuichiro', 'Daimaru', 12, 'Midfielder', 310, [
                'speed' => 64, 'stamina' => 74, 'defense' => 30, 'attack' => 24, 'shot' => 22, 'pass' => 25, 'dribble' => 22, 'block' => 22, 'intercept' => 22, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takayuki', 'Shiota', 12, 'Midfielder', 225, [
                'speed' => 56, 'stamina' => 65, 'defense' => 26, 'attack' => 21, 'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Nobuo', 'Aimoto', 12, 'Midfielder', 230, [
                'speed' => 55, 'stamina' => 64, 'defense' => 26, 'attack' => 21, 'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hiroshi', 'Tamai', 12, 'Midfielder', 235, [
                'speed' => 59, 'stamina' => 67, 'defense' => 27, 'attack' => 22, 'shot' => 21, 'pass' => 23, 'dribble' => 21, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yoshiharu', 'Ono', 12, 'Forward', 240, [
                'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 26, 'shot' => 23, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Masao', 'Tachibana', 13, 'Forward', 425, [
                'speed' => 75, 'stamina' => 75, 'defense' => 20, 'attack' => 38, 'shot' => 29, 'pass' => 28, 'dribble' => 26, 'block' => 18, 'intercept' => 20, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazuo', 'Tachibana', 13, 'Forward', 425, [
                'speed' => 75, 'stamina' => 75, 'defense' => 20, 'attack' => 38, 'shot' => 29, 'pass' => 28, 'dribble' => 26, 'block' => 18, 'intercept' => 20, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Azuma-ichi
            ['Ryota', 'Tsuji', 12, 'Goalkeeper', 225, [
                'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16, 'shot' => 15, 'pass' => 18, 'dribble' => 15, 'block' => 21, 'intercept' => 22, 'tackle' => 18, 'hand_save' => 25, 'punch_save' => 22
            ]],
            ['Junji', 'Yamada', 12, 'Defender', 235, [
                'speed' => 60, 'stamina' => 67, 'defense' => 28, 'attack' => 22, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Makoto', 'Soda', 12, 'Defender', 430, [
                'speed' => 70, 'stamina' => 85, 'defense' => 38, 'attack' => 24, 'shot' => 25, 'pass' => 24, 'dribble' => 20, 'block' => 28, 'intercept' => 28, 'tackle' => 30, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Daigo', 'Sasaki', 12, 'Defender', 240, [
                'speed' => 58, 'stamina' => 68, 'defense' => 29, 'attack' => 21, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Tatsuya', 'Hayashi', 12, 'Midfielder', 230, [
                'speed' => 57, 'stamina' => 66, 'defense' => 26, 'attack' => 22, 'shot' => 21, 'pass' => 23, 'dribble' => 21, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koji', 'Yoshida', 12, 'Midfielder', 225, [
                'speed' => 56, 'stamina' => 65, 'defense' => 26, 'attack' => 21, 'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yohei', 'Kuramochi', 12, 'Midfielder', 220, [
                'speed' => 55, 'stamina' => 64, 'defense' => 25, 'attack' => 21, 'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Toru', 'Nakai', 12, 'Midfielder', 235, [
                'speed' => 59, 'stamina' => 67, 'defense' => 26, 'attack' => 22, 'shot' => 21, 'pass' => 23, 'dribble' => 21, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazuyasu', 'Onodera', 12, 'Forward', 240, [
                'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 26, 'shot' => 24, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Mitsuru', 'Ide', 12, 'Forward', 230, [
                'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 26, 'shot' => 24, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shohei', 'Mihashi', 12, 'Forward', 240, [
                'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 26, 'shot' => 24, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Hirado
            ['Akira', 'Hatakeyama', 12, 'Goalkeeper', 225, [
                'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16, 'shot' => 15, 'pass' => 18, 'dribble' => 15, 'block' => 21, 'intercept' => 22, 'tackle' => 18, 'hand_save' => 25, 'punch_save' => 22
            ]],
            ['Kazuaki', 'Soda', 12, 'Defender', 235, [
                'speed' => 60, 'stamina' => 67, 'defense' => 28, 'attack' => 22, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hiroshi', 'Jito', 13, 'Defender', 440, [
                'speed' => 65, 'stamina' => 85, 'defense' => 36, 'attack' => 26, 'shot' => 22, 'pass' => 24, 'dribble' => 23, 'block' => 30, 'intercept' => 28, 'tackle' => 30, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Toshio', 'Akizawa', 12, 'Defender', 240, [
                'speed' => 58, 'stamina' => 68, 'defense' => 29, 'attack' => 21, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shinji', 'Noda', 12, 'Midfielder', 230, [
                'speed' => 57, 'stamina' => 66, 'defense' => 26, 'attack' => 22, 'shot' => 21, 'pass' => 23, 'dribble' => 21, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Tsutomu', 'Nagaoka', 12, 'Midfielder', 225, [
                'speed' => 56, 'stamina' => 65, 'defense' => 26, 'attack' => 21, 'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koji', 'Nakajo', 12, 'Midfielder', 220, [
                'speed' => 55, 'stamina' => 64, 'defense' => 25, 'attack' => 21, 'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shinji', 'Morisue', 12, 'Midfielder', 235, [
                'speed' => 59, 'stamina' => 67, 'defense' => 27, 'attack' => 22, 'shot' => 21, 'pass' => 23, 'dribble' => 21, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazuo', 'Takeno', 12, 'Midfielder', 240, [
                'speed' => 58, 'stamina' => 66, 'defense' => 27, 'attack' => 23, 'shot' => 22, 'pass' => 23, 'dribble' => 21, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Katsumi', 'Himeji', 12, 'Forward', 230, [
                'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 26, 'shot' => 24, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Mitsuru', 'Sano', 13, 'Forward', 340, [
                'speed' => 62, 'stamina' => 76, 'defense' => 20, 'attack' => 36, 'shot' => 28, 'pass' => 22, 'dribble' => 26, 'block' => 18, 'intercept' => 18, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Otomo
            ['Isamu', 'Ichijo', 12, 'Goalkeeper', 275, [
                'speed' => 50, 'stamina' => 65, 'defense' => 29, 'attack' => 18, 'shot' => 16, 'pass' => 18, 'dribble' => 15, 'block' => 21, 'intercept' => 22, 'tackle' => 18, 'hand_save' => 24, 'punch_save' => 22
            ]],
            ['Masaki', 'Yoshikawa', 12, 'Defender', 285, [
                'speed' => 52, 'stamina' => 67, 'defense' => 30, 'attack' => 21, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koji', 'Nishio', 12, 'Defender', 350, [
                'speed' => 55, 'stamina' => 70, 'defense' => 34, 'attack' => 22, 'shot' => 19, 'pass' => 22, 'dribble' => 19, 'block' => 26, 'intercept' => 27, 'tackle' => 27, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Masao', 'Nakayama', 12, 'Defender', 350, [
                'speed' => 55, 'stamina' => 70, 'defense' => 34, 'attack' => 22, 'shot' => 19, 'pass' => 22, 'dribble' => 19, 'block' => 27, 'intercept' => 26, 'tackle' => 27, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kozo', 'Kawada', 12, 'Defender', 290, [
                'speed' => 53, 'stamina' => 68, 'defense' => 31, 'attack' => 21, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 25, 'intercept' => 24, 'tackle' => 26, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Toru', 'Hiraoka', 12, 'Midfielder', 280, [
                'speed' => 51, 'stamina' => 66, 'defense' => 25, 'attack' => 23, 'shot' => 21, 'pass' => 23, 'dribble' => 21, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takeshi', 'Kishida', 12, 'Midfielder', 350, [
                'speed' => 55, 'stamina' => 70, 'defense' => 26, 'attack' => 26, 'shot' => 22, 'pass' => 25, 'dribble' => 23, 'block' => 20, 'intercept' => 23, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hanji', 'Urabe', 12, 'Midfielder', 400, [
                'speed' => 60, 'stamina' => 75, 'defense' => 30, 'attack' => 30, 'shot' => 24, 'pass' => 25, 'dribble' => 25, 'block' => 22, 'intercept' => 25, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shingo', 'Tadami', 12, 'Midfielder', 290, [
                'speed' => 53, 'stamina' => 68, 'defense' => 25, 'attack' => 25, 'shot' => 23, 'pass' => 24, 'dribble' => 22, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Akio', 'Nakao', 12, 'Forward', 300, [
                'speed' => 55, 'stamina' => 70, 'defense' => 18, 'attack' => 31, 'shot' => 26, 'pass' => 21, 'dribble' => 23, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shun', 'Nitta', 12, 'Forward', 475, [
                'speed' => 70, 'stamina' => 75, 'defense' => 18, 'attack' => 39, 'shot' => 29, 'pass' => 23, 'dribble' => 26, 'block' => 18, 'intercept' => 22, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Meiwa
            ['Tetsuji', 'Murasawa', 12, 'Goalkeeper', 225, [
                'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16, 'shot' => 15, 'pass' => 18, 'dribble' => 15, 'block' => 21, 'intercept' => 22, 'tackle' => 18, 'hand_save' => 25, 'punch_save' => 22
            ]],
            ['Keiji', 'Kawagoe', 12, 'Defender', 230, [
                'speed' => 60, 'stamina' => 68, 'defense' => 28, 'attack' => 20, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 24, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hiroshi', 'Ishii', 12, 'Defender', 235, [
                'speed' => 58, 'stamina' => 66, 'defense' => 29, 'attack' => 21, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Toshiyuki', 'Takagi', 12, 'Defender', 240, [
                'speed' => 57, 'stamina' => 67, 'defense' => 29, 'attack' => 21, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 25, 'intercept' => 24, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Motoharu', 'Nagano', 12, 'Defender', 325, [
                'speed' => 65, 'stamina' => 69, 'defense' => 32, 'attack' => 22, 'shot' => 19, 'pass' => 20, 'dribble' => 18, 'block' => 26, 'intercept' => 25, 'tackle' => 27, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shinishi', 'Sakamoto', 12, 'Midfielder', 315, [
                'speed' => 64, 'stamina' => 70, 'defense' => 30, 'attack' => 28, 'shot' => 25, 'pass' => 25, 'dribble' => 24, 'block' => 22, 'intercept' => 23, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kuniaki', 'Narita', 12, 'Midfielder', 310, [
                'speed' => 63, 'stamina' => 69, 'defense' => 29, 'attack' => 27, 'shot' => 24, 'pass' => 25, 'dribble' => 24, 'block' => 22, 'intercept' => 23, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hiromichi', 'Hori', 12, 'Midfielder', 310, [
                'speed' => 63, 'stamina' => 69, 'defense' => 29, 'attack' => 27, 'shot' => 24, 'pass' => 25, 'dribble' => 24, 'block' => 22, 'intercept' => 23, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazushige', 'Enomoto', 12, 'Midfielder', 225, [
                'speed' => 62, 'stamina' => 64, 'defense' => 24, 'attack' => 26, 'shot' => 23, 'pass' => 24, 'dribble' => 23, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Noboru', 'Sawaki', 12, 'Forward', 300, [
                'speed' => 66, 'stamina' => 70, 'defense' => 20, 'attack' => 32, 'shot' => 27, 'pass' => 22, 'dribble' => 24, 'block' => 18, 'intercept' => 18, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yuichi', 'Suenaga', 12, 'Forward', 320, [
                'speed' => 65, 'stamina' => 69, 'defense' => 20, 'attack' => 34, 'shot' => 28, 'pass' => 22, 'dribble' => 25, 'block' => 18, 'intercept' => 18, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Nakahara
            ['Goro', 'Kawakami', 12, 'Goalkeeper', 250, [
                'speed' => 40, 'stamina' => 60, 'defense' => 26, 'attack' => 16, 'shot' => 15, 'pass' => 17, 'dribble' => 15, 'block' => 20, 'intercept' => 20, 'tackle' => 17, 'hand_save' => 23, 'punch_save' => 21
            ]],
            ['Yuichi', 'Masumoto', 12, 'Midfielder', 240, [
                'speed' => 50, 'stamina' => 60, 'defense' => 22, 'attack' => 22, 'shot' => 21, 'pass' => 22, 'dribble' => 21, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Keisuke', 'Haranashi', 12, 'Defender', 230, [
                'speed' => 44, 'stamina' => 57, 'defense' => 25, 'attack' => 18, 'shot' => 16, 'pass' => 17, 'dribble' => 16, 'block' => 21, 'intercept' => 19, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takamasa', 'Fujita', 12, 'Defender', 235, [
                'speed' => 43, 'stamina' => 58, 'defense' => 25, 'attack' => 18, 'shot' => 16, 'pass' => 17, 'dribble' => 16, 'block' => 22, 'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Jin', 'Toda', 12, 'Defender', 225, [
                'speed' => 42, 'stamina' => 56, 'defense' => 24, 'attack' => 17, 'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 21, 'intercept' => 19, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ken', 'Nagatani', 12, 'Midfielder', 245, [
                'speed' => 50, 'stamina' => 60, 'defense' => 22, 'attack' => 22, 'shot' => 21, 'pass' => 22, 'dribble' => 21, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shunta', 'Harukawa', 12, 'Midfielder', 240, [
                'speed' => 48, 'stamina' => 59, 'defense' => 21, 'attack' => 21, 'shot' => 20, 'pass' => 21, 'dribble' => 20, 'block' => 19, 'intercept' => 19, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Susumu', 'Itao', 12, 'Midfielder', 235, [
                'speed' => 46, 'stamina' => 57, 'defense' => 21, 'attack' => 23, 'shot' => 22, 'pass' => 22, 'dribble' => 21, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Goro', 'Kurita', 12, 'Midfielder', 230, [
                'speed' => 45, 'stamina' => 56, 'defense' => 21, 'attack' => 21, 'shot' => 20, 'pass' => 21, 'dribble' => 20, 'block' => 19, 'intercept' => 19, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takeshi', 'Asada', 12, 'Forward', 250, [
                'speed' => 49, 'stamina' => 60, 'defense' => 20, 'attack' => 26, 'shot' => 24, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shingo', 'Aoi', 12, 'Forward', 450, [
                'speed' => 75, 'stamina' => 72, 'defense' => 25, 'attack' => 36, 'shot' => 29, 'pass' => 25, 'dribble' => 28, 'block' => 18, 'intercept' => 22, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Naniwa
            ['Taichi', 'Nakanishi', 12, 'Goalkeeper', 400, [
                'speed' => 40, 'stamina' => 70, 'defense' => 38, 'attack' => 16, 'shot' => 15, 'pass' => 18, 'dribble' => 15, 'block' => 26, 'intercept' => 24, 'tackle' => 18, 'hand_save' => 29, 'punch_save' => 29
            ]],
            ['Hiroshi', 'Tsusaki', 12, 'Defender', 240, [
                'speed' => 45, 'stamina' => 55, 'defense' => 24, 'attack' => 20, 'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 21, 'intercept' => 19, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazuya', 'Kosaka', 12, 'Defender', 235, [
                'speed' => 43, 'stamina' => 58, 'defense' => 25, 'attack' => 19, 'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 22, 'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shinji', 'Yoshimoto', 12, 'Defender', 230, [
                'speed' => 44, 'stamina' => 57, 'defense' => 24, 'attack' => 18, 'shot' => 16, 'pass' => 17, 'dribble' => 16, 'block' => 21, 'intercept' => 20, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Daisuke', 'Tennoji', 12, 'Defender', 225, [
                'speed' => 42, 'stamina' => 56, 'defense' => 24, 'attack' => 17, 'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 21, 'intercept' => 19, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Masato', 'Dojima', 12, 'Midfielder', 245, [
                'speed' => 50, 'stamina' => 60, 'defense' => 22, 'attack' => 22, 'shot' => 21, 'pass' => 22, 'dribble' => 21, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ryo', 'Maeda', 12, 'Midfielder', 240, [
                'speed' => 48, 'stamina' => 59, 'defense' => 21, 'attack' => 21, 'shot' => 20, 'pass' => 21, 'dribble' => 20, 'block' => 19, 'intercept' => 19, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kenji', 'Shirai', 12, 'Midfielder', 235, [
                'speed' => 46, 'stamina' => 57, 'defense' => 21, 'attack' => 23, 'shot' => 22, 'pass' => 22, 'dribble' => 21, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yuta', 'Ogami', 12, 'Midfielder', 230, [
                'speed' => 45, 'stamina' => 56, 'defense' => 21, 'attack' => 21, 'shot' => 20, 'pass' => 21, 'dribble' => 20, 'block' => 19, 'intercept' => 19, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Satoshi', 'Takayanagi', 12, 'Forward', 250, [
                'speed' => 49, 'stamina' => 60, 'defense' => 20, 'attack' => 26, 'shot' => 24, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Tetsuya', 'Marui', 12, 'Forward', 240, [
                'speed' => 48, 'stamina' => 58, 'defense' => 19, 'attack' => 24, 'shot' => 23, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Minawi
            ['Hajime', 'Asakura', 12, 'Goalkeeper', 225, [
                'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16, 'shot' => 15, 'pass' => 18, 'dribble' => 15, 'block' => 21, 'intercept' => 22, 'tackle' => 18, 'hand_save' => 25, 'punch_save' => 22
            ]],
            ['Daichi', 'Azuma', 12, 'Defender', 230, [
                'speed' => 60, 'stamina' => 68, 'defense' => 28, 'attack' => 20, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 24, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shinji', 'Takahama', 12, 'Defender', 235, [
                'speed' => 58, 'stamina' => 66, 'defense' => 29, 'attack' => 21, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ryu', 'Kawanoe', 12, 'Defender', 240, [
                'speed' => 57, 'stamina' => 67, 'defense' => 29, 'attack' => 21, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 25, 'intercept' => 24, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takashi', 'Iyo', 12, 'Defender', 245, [
                'speed' => 56, 'stamina' => 69, 'defense' => 28, 'attack' => 22, 'shot' => 18, 'pass' => 19, 'dribble' => 17, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koji', 'Tosa', 12, 'Midfielder', 210, [
                'speed' => 60, 'stamina' => 62, 'defense' => 24, 'attack' => 26, 'shot' => 23, 'pass' => 24, 'dribble' => 23, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hiroto', 'Shintani', 12, 'Midfielder', 215, [
                'speed' => 61, 'stamina' => 63, 'defense' => 24, 'attack' => 27, 'shot' => 24, 'pass' => 25, 'dribble' => 24, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Tetsuo', 'Ishida', 12, 'Midfielder', 325, [
                'speed' => 69, 'stamina' => 74, 'defense' => 25, 'attack' => 34, 'shot' => 28, 'pass' => 24, 'dribble' => 26, 'block' => 20, 'intercept' => 22, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Masaru', 'Hirayama', 12, 'Midfielder', 220, [
                'speed' => 62, 'stamina' => 64, 'defense' => 24, 'attack' => 26, 'shot' => 23, 'pass' => 24, 'dribble' => 23, 'block' => 20, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazuki', 'Seto', 12, 'Forward', 235, [
                'speed' => 63, 'stamina' => 65, 'defense' => 20, 'attack' => 30, 'shot' => 26, 'pass' => 22, 'dribble' => 24, 'block' => 18, 'intercept' => 18, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazuto', 'Takei', 12, 'Forward', 250, [
                'speed' => 64, 'stamina' => 66, 'defense' => 20, 'attack' => 31, 'shot' => 27, 'pass' => 22, 'dribble' => 24, 'block' => 18, 'intercept' => 18, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Shimizu
            ['Morimichi', 'Kawakami', 12, 'Goalkeeper', 300, [
                'speed' => 40, 'stamina' => 65, 'defense' => 26, 'attack' => 16, 'shot' => 15, 'pass' => 17, 'dribble' => 15, 'block' => 21, 'intercept' => 20, 'tackle' => 17, 'hand_save' => 24, 'punch_save' => 22
            ]],
            ['Takeshi', 'Kudo', 12, 'Defender', 240, [
                'speed' => 45, 'stamina' => 55, 'defense' => 24, 'attack' => 20, 'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 21, 'intercept' => 19, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ichiro', 'Kanda', 12, 'Defender', 235, [
                'speed' => 43, 'stamina' => 58, 'defense' => 25, 'attack' => 19, 'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 22, 'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yuto', 'Ibaraki', 12, 'Defender', 230, [
                'speed' => 44, 'stamina' => 57, 'defense' => 24, 'attack' => 18, 'shot' => 16, 'pass' => 17, 'dribble' => 16, 'block' => 21, 'intercept' => 20, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hiroshi', 'Suzuki', 12, 'Defender', 225, [
                'speed' => 42, 'stamina' => 56, 'defense' => 24, 'attack' => 17, 'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 21, 'intercept' => 19, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Daisuke', 'Takada', 12, 'Midfielder', 245, [
                'speed' => 50, 'stamina' => 60, 'defense' => 22, 'attack' => 22, 'shot' => 21, 'pass' => 22, 'dribble' => 21, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ryota', 'Nakao', 12, 'Midfielder', 240, [
                'speed' => 48, 'stamina' => 59, 'defense' => 21, 'attack' => 21, 'shot' => 20, 'pass' => 21, 'dribble' => 20, 'block' => 19, 'intercept' => 19, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shinji', 'Iimura', 12, 'Midfielder', 235, [
                'speed' => 46, 'stamina' => 57, 'defense' => 21, 'attack' => 23, 'shot' => 22, 'pass' => 22, 'dribble' => 21, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koji', 'Murakami', 12, 'Midfielder', 230, [
                'speed' => 45, 'stamina' => 56, 'defense' => 21, 'attack' => 21, 'shot' => 20, 'pass' => 21, 'dribble' => 20, 'block' => 19, 'intercept' => 19, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazumasa', 'Kato', 12, 'Forward', 250, [
                'speed' => 49, 'stamina' => 60, 'defense' => 20, 'attack' => 26, 'shot' => 24, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takashi', 'Obayashi', 12, 'Forward', 240, [
                'speed' => 48, 'stamina' => 58, 'defense' => 19, 'attack' => 24, 'shot' => 23, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],

            // Shimada
            ['Etsuo', 'Nagai', 12, 'Goalkeeper', 250, [
                'speed' => 40, 'stamina' => 60, 'defense' => 26, 'attack' => 16, 'shot' => 15, 'pass' => 17, 'dribble' => 15, 'block' => 21, 'intercept' => 20, 'tackle' => 17, 'hand_save' => 24, 'punch_save' => 22
            ]],
            ['Ikushi', 'Ito', 12, 'Defender', 240, [
                'speed' => 45, 'stamina' => 55, 'defense' => 24, 'attack' => 20, 'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 21, 'intercept' => 19, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koichi', 'Fujisawa', 12, 'Defender', 235, [
                'speed' => 43, 'stamina' => 58, 'defense' => 25, 'attack' => 19, 'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 22, 'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Nemto', 'Takahashi', 12, 'Defender', 230, [
                'speed' => 44, 'stamina' => 57, 'defense' => 24, 'attack' => 18, 'shot' => 16, 'pass' => 17, 'dribble' => 16, 'block' => 21, 'intercept' => 20, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Jo', 'Kimura', 12, 'Midfielder', 245, [
                'speed' => 50, 'stamina' => 60, 'defense' => 22, 'attack' => 22, 'shot' => 21, 'pass' => 22, 'dribble' => 21, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koji', 'Ishikawa', 12, 'Midfielder', 240, [
                'speed' => 48, 'stamina' => 59, 'defense' => 21, 'attack' => 21, 'shot' => 20, 'pass' => 21, 'dribble' => 20, 'block' => 19, 'intercept' => 19, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takushi', 'Hashimoto', 12, 'Forward', 235, [
                'speed' => 46, 'stamina' => 57, 'defense' => 20, 'attack' => 24, 'shot' => 23, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Junichi', 'Nagasaki', 12, 'Forward', 250, [
                'speed' => 59, 'stamina' => 60, 'defense' => 20, 'attack' => 26, 'shot' => 24, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Light', 'Nakamura', 12, 'Midfielder', 240, [
                'speed' => 47, 'stamina' => 58, 'defense' => 21, 'attack' => 21, 'shot' => 20, 'pass' => 21, 'dribble' => 20, 'block' => 19, 'intercept' => 19, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Masayuki', 'Jinbo', 12, 'Midfielder', 245, [
                'speed' => 50, 'stamina' => 59, 'defense' => 22, 'attack' => 22, 'shot' => 21, 'pass' => 22, 'dribble' => 21, 'block' => 19, 'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Naoki', 'Wesugi', 12, 'Forward', 250, [
                'speed' => 50, 'stamina' => 60, 'defense' => 20, 'attack' => 25, 'shot' => 24, 'pass' => 21, 'dribble' => 22, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],


            // Real Seven – Not contract
            ['Michel', 'Yamada', 13, 'Goalkeeper', 300, [
                'speed' => 60, 'stamina' => 70, 'defense' => 32, 'attack' => 18, 'shot' => 16, 'pass' => 19, 'dribble' => 17, 'block' => 22, 'intercept' => 23, 'tackle' => 18, 'hand_save' => 26, 'punch_save' => 24
            ]],
            ['Yuji', 'Sakaki', 13, 'Defender', 285, [
                'speed' => 58, 'stamina' => 67, 'defense' => 30, 'attack' => 22, 'shot' => 18, 'pass' => 20, 'dribble' => 18, 'block' => 24, 'intercept' => 23, 'tackle' => 25, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yuji', 'Soga', 13, 'Defender', 290, [
                'speed' => 60, 'stamina' => 68, 'defense' => 31, 'attack' => 23, 'shot' => 19, 'pass' => 20, 'dribble' => 18, 'block' => 25, 'intercept' => 24, 'tackle' => 26, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Gakuto', 'Igawa', 13, 'Midfielder', 295, [
                'speed' => 62, 'stamina' => 68, 'defense' => 28, 'attack' => 25, 'shot' => 23, 'pass' => 24, 'dribble' => 23, 'block' => 21, 'intercept' => 22, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kotaru', 'Furukawa', 13, 'Midfielder', 300, [
                'speed' => 63, 'stamina' => 69, 'defense' => 29, 'attack' => 26, 'shot' => 24, 'pass' => 25, 'dribble' => 24, 'block' => 21, 'intercept' => 22, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takashi', 'Sugimoto', 13, 'Midfielder', 305, [
                'speed' => 65, 'stamina' => 70, 'defense' => 30, 'attack' => 27, 'shot' => 25, 'pass' => 26, 'dribble' => 25, 'block' => 22, 'intercept' => 23, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Nobuyuki', 'Yumikura', 13, 'Midfielder', 310, [
                'speed' => 66, 'stamina' => 71, 'defense' => 31, 'attack' => 28, 'shot' => 26, 'pass' => 27, 'dribble' => 26, 'block' => 22, 'intercept' => 23, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Toshiya', 'Okano', 13, 'Midfielder', 300, [
                'speed' => 64, 'stamina' => 69, 'defense' => 29, 'attack' => 27, 'shot' => 25, 'pass' => 26, 'dribble' => 25, 'block' => 22, 'intercept' => 23, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shinnosuke', 'Kazami', 13, 'Forward', 315, [
                'speed' => 67, 'stamina' => 70, 'defense' => 22, 'attack' => 36, 'shot' => 28, 'pass' => 22, 'dribble' => 26, 'block' => 18, 'intercept' => 18, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ryoma', 'Hino', 12, 'Forward', 445, [
                'speed' => 70, 'stamina' => 80, 'defense' => 20, 'attack' => 38, 'shot' => 30, 'pass' => 20, 'dribble' => 27, 'block' => 18, 'intercept' => 18, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],


            // Other – Not contract (filler players)
            ['Masaru', 'Ito', 12, 'Midfielder', 210, [
                'speed' => 50, 'stamina' => 55, 'defense' => 22, 'attack' => 24, 'shot' => 21, 'pass' => 22, 'dribble' => 21, 'block' => 18, 'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takeshi', 'Kira', 12, 'Forward', 175, [
                'speed' => 45, 'stamina' => 50, 'defense' => 18, 'attack' => 23, 'shot' => 22, 'pass' => 18, 'dribble' => 20, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Daichi', 'Kakeru', 12, 'Midfielder', 210, [
                'speed' => 52, 'stamina' => 57, 'defense' => 23, 'attack' => 25, 'shot' => 22, 'pass' => 23, 'dribble' => 22, 'block' => 18, 'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yayoi', 'Aoba', 12, 'Defender', 205, [
                'speed' => 48, 'stamina' => 54, 'defense' => 26, 'attack' => 20, 'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 22, 'intercept' => 21, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kuniharu', 'Uematsu', 12, 'Defender', 200, [
                'speed' => 47, 'stamina' => 52, 'defense' => 25, 'attack' => 20, 'shot' => 16, 'pass' => 17, 'dribble' => 16, 'block' => 21, 'intercept' => 20, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Katsutoshi', 'Hasegawa', 13, 'Midfielder', 210, [
                'speed' => 48, 'stamina' => 55, 'defense' => 22, 'attack' => 24, 'shot' => 22, 'pass' => 22, 'dribble' => 21, 'block' => 18, 'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Sho', 'Kazama', 12, 'Defender', 210, [
                'speed' => 50, 'stamina' => 55, 'defense' => 27, 'attack' => 21, 'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 23, 'intercept' => 22, 'tackle' => 23, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Masaki', 'Kozou', 13, 'Forward', 210, [
                'speed' => 53, 'stamina' => 55, 'defense' => 18, 'attack' => 26, 'shot' => 23, 'pass' => 19, 'dribble' => 21, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takuya', 'Furano', 12, 'Midfielder', 200, [
                'speed' => 48, 'stamina' => 52, 'defense' => 22, 'attack' => 22, 'shot' => 21, 'pass' => 21, 'dribble' => 20, 'block' => 18, 'intercept' => 18, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazuya', 'Kano', 12, 'Forward', 205, [
                'speed' => 49, 'stamina' => 53, 'defense' => 20, 'attack' => 24, 'shot' => 22, 'pass' => 20, 'dribble' => 21, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Haruto', 'Kobayashi', 12, 'Midfielder', 180, [
                'speed' => 45, 'stamina' => 50, 'defense' => 18, 'attack' => 22, 'shot' => 21, 'pass' => 20, 'dribble' => 20, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Riku', 'Yamamoto', 12, 'Defender', 175, [
                'speed' => 42, 'stamina' => 48, 'defense' => 23, 'attack' => 18, 'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 20, 'intercept' => 19, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yuto', 'Tanaka', 12, 'Forward', 170, [
                'speed' => 50, 'stamina' => 45, 'defense' => 16, 'attack' => 25, 'shot' => 23, 'pass' => 19, 'dribble' => 22, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Sota', 'Saito', 12, 'Goalkeeper', 165, [
                'speed' => 38, 'stamina' => 47, 'defense' => 25, 'attack' => 15, 'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 20, 'intercept' => 20, 'tackle' => 17, 'hand_save' => 22, 'punch_save' => 20
            ]],
            ['Daiki', 'Nishimura', 12, 'Midfielder', 160, [
                'speed' => 43, 'stamina' => 46, 'defense' => 18, 'attack' => 21, 'shot' => 19, 'pass' => 20, 'dribble' => 19, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kaito', 'Fujimoto', 12, 'Defender', 155, [
                'speed' => 40, 'stamina' => 45, 'defense' => 21, 'attack' => 17, 'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 19, 'intercept' => 18, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Hinata', 'Kimura', 12, 'Forward', 150, [
                'speed' => 48, 'stamina' => 43, 'defense' => 16, 'attack' => 23, 'shot' => 22, 'pass' => 18, 'dribble' => 21, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ren', 'Shimizu', 12, 'Midfielder', 145, [
                'speed' => 42, 'stamina' => 42, 'defense' => 17, 'attack' => 20, 'shot' => 18, 'pass' => 19, 'dribble' => 18, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koki', 'Hayashi', 12, 'Defender', 140, [
                'speed' => 39, 'stamina' => 40, 'defense' => 20, 'attack' => 16, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 18, 'intercept' => 18, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yuma', 'Ishikawa', 12, 'Goalkeeper', 135, [
                'speed' => 36, 'stamina' => 42, 'defense' => 22, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 19, 'intercept' => 19, 'tackle' => 16, 'hand_save' => 20, 'punch_save' => 19
            ]],
            ['Shion', 'Matsui', 12, 'Midfielder', 130, [
                'speed' => 40, 'stamina' => 40, 'defense' => 16, 'attack' => 19, 'shot' => 18, 'pass' => 18, 'dribble' => 18, 'block' => 16, 'intercept' => 17, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Keita', 'Inoue', 12, 'Defender', 125, [
                'speed' => 38, 'stamina' => 38, 'defense' => 19, 'attack' => 16, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 18, 'intercept' => 17, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takumi', 'Yamada', 12, 'Forward', 120, [
                'speed' => 46, 'stamina' => 37, 'defense' => 15, 'attack' => 22, 'shot' => 21, 'pass' => 17, 'dribble' => 20, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ryota', 'Kondo', 12, 'Midfielder', 115, [
                'speed' => 39, 'stamina' => 35, 'defense' => 15, 'attack' => 18, 'shot' => 17, 'pass' => 17, 'dribble' => 17, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Sho', 'Suzuki', 12, 'Defender', 110, [
                'speed' => 35, 'stamina' => 36, 'defense' => 18, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 17, 'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kensuke', 'Hara', 12, 'Goalkeeper', 105, [
                'speed' => 33, 'stamina' => 35, 'defense' => 21, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 18, 'intercept' => 18, 'tackle' => 16, 'hand_save' => 19, 'punch_save' => 18
            ]],
            ['Aoi', 'Ogawa', 12, 'Midfielder', 100, [
                'speed' => 38, 'stamina' => 34, 'defense' => 15, 'attack' => 17, 'shot' => 16, 'pass' => 16, 'dribble' => 16, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Haruki', 'Mori', 12, 'Midfielder', 100, [
                'speed' => 32, 'stamina' => 34, 'defense' => 15, 'attack' => 16, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kazuya', 'Kobayashi', 12, 'Defender', 95, [
                'speed' => 30, 'stamina' => 32, 'defense' => 16, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 17, 'intercept' => 16, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takashi', 'Yamada', 12, 'Forward', 90, [
                'speed' => 35, 'stamina' => 30, 'defense' => 15, 'attack' => 17, 'shot' => 17, 'pass' => 15, 'dribble' => 16, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Satoshi', 'Suzuki', 12, 'Goalkeeper', 85, [
                'speed' => 28, 'stamina' => 31, 'defense' => 18, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 17, 'intercept' => 17, 'tackle' => 16, 'hand_save' => 18, 'punch_save' => 17
            ]],
            ['Ryosuke', 'Tanaka', 12, 'Midfielder', 80, [
                'speed' => 31, 'stamina' => 30, 'defense' => 15, 'attack' => 16, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Naoki', 'Fujimoto', 12, 'Defender', 75, [
                'speed' => 28, 'stamina' => 28, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yuki', 'Kato', 12, 'Forward', 70, [
                'speed' => 33, 'stamina' => 27, 'defense' => 15, 'attack' => 16, 'shot' => 16, 'pass' => 15, 'dribble' => 16, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koji', 'Nakamura', 12, 'Midfielder', 65, [
                'speed' => 30, 'stamina' => 26, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Shun', 'Sato', 12, 'Defender', 60, [
                'speed' => 27, 'stamina' => 25, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takeshi', 'Matsumoto', 12, 'Goalkeeper', 55, [
                'speed' => 25, 'stamina' => 26, 'defense' => 16, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 15, 'hand_save' => 17, 'punch_save' => 16
            ]],
            ['Hiroto', 'Watanabe', 12, 'Midfielder', 50, [
                'speed' => 29, 'stamina' => 24, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Kenta', 'Kimura', 12, 'Defender', 100, [
                'speed' => 26, 'stamina' => 22, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Riku', 'Ito', 12, 'Forward', 95, [
                'speed' => 32, 'stamina' => 21, 'defense' => 15, 'attack' => 16, 'shot' => 16, 'pass' => 15, 'dribble' => 16, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Yuta', 'Sakai', 12, 'Midfielder', 90, [
                'speed' => 28, 'stamina' => 20, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Daichi', 'Nakajima', 12, 'Defender', 85, [
                'speed' => 25, 'stamina' => 18, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Koki', 'Yoshida', 12, 'Goalkeeper', 80, [
                'speed' => 23, 'stamina' => 19, 'defense' => 16, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 15, 'hand_save' => 17, 'punch_save' => 16
            ]],
            ['Shota', 'Harada', 12, 'Midfielder', 75, [
                'speed' => 27, 'stamina' => 18, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Takuya', 'Hasegawa', 12, 'Defender', 70, [
                'speed' => 24, 'stamina' => 16, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Ren', 'Yamashita', 12, 'Forward', 65, [
                'speed' => 30, 'stamina' => 15, 'defense' => 15, 'attack' => 16, 'shot' => 16, 'pass' => 15, 'dribble' => 16, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
            ['Haru', 'Ogawa', 12, 'Midfielder', 60, [
                'speed' => 26, 'stamina' => 14, 'defense' => 15, 'attack' => 15, 'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 16, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ]],
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

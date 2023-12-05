<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $players = [
            ['Tsubasa', 'Ozora', 12, 'Midfielder', 15000, ['speed' => 80, 'stamina' => 85, 'defense' => 70, 'attack' => 90]],
            ['Kojiro', 'Hyuga', 13, 'Forward', 14000, ['speed' => 78, 'stamina' => 88, 'defense' => 65, 'attack' => 92]],
            ['Genzo', 'Wakabayashi', 12, 'Goalkeeper', 15000, ['speed' => 60, 'stamina' => 90, 'defense' => 95, 'attack' => 60]],
            ['Taro', 'Misaki', 12, 'Midfielder', 12000, ['speed' => 75, 'stamina' => 80, 'defense' => 68, 'attack' => 86]],
            ['Ryo', 'Ishizaki', 12, 'Defender', 10000, ['speed' => 65, 'stamina' => 80, 'defense' => 75, 'attack' => 65]],
            ['Ken', 'Wakashimazu', 13, 'Goalkeeper', 13000, ['speed' => 32, 'stamina' => 85, 'defense' => 90, 'attack' => 62]],
            ['Hikaru', 'Matsuyama', 13, 'Midfielder', 11000, ['speed' => 70, 'stamina' => 87, 'defense' => 82, 'attack' => 75]],
            ['Shingo', 'Takasugi', 12, 'Defender', 9500, ['speed' => 60, 'stamina' => 82, 'defense' => 78, 'attack' => 60]],
            ['Jun', 'Misugi', 13, 'Midfielder', 13500, ['speed' => 83, 'stamina' => 50, 'defense' => 85, 'attack' => 85]],
            ['Mamoru', 'Izawa', 12, 'Midfielder', 9000, ['speed' => 68, 'stamina' => 77, 'defense' => 65, 'attack' => 70]],
            ['Teppei', 'Kisugi', 13, 'Forward', 11000, ['speed' => 70, 'stamina' => 80, 'defense' => 60, 'attack' => 78]],
            ['Hiroshi', 'Jito', 13, 'Defender', 10500, ['speed' => 65, 'stamina' => 85, 'defense' => 82, 'attack' => 67]],
            ['Yuzo', 'Morisaki', 12, 'Goalkeeper', 8500, ['speed' => 60, 'stamina' => 80, 'defense' => 85, 'attack' => 55]],
            ['Takeshi', 'Sawada', 11, 'Defender', 8000, ['speed' => 60, 'stamina' => 75, 'defense' => 70, 'attack' => 60]],
            ['Takeshi', 'Kishida', 12, 'Midfielder', 7000, ['speed' => 60, 'stamina' => 65, 'defense' => 55, 'attack' => 60]],
            ['Takashi', 'Sugimoto', 12, 'Forward', 6000, ['speed' => 60, 'stamina' => 65, 'defense' => 50, 'attack' => 65]],
            ['Takeshi', 'Kira', 12, 'Forward', 5500, ['speed' => 55, 'stamina' => 60, 'defense' => 45, 'attack' => 60]],
            ['Hajime', 'Taki', 12, 'Forward', 5000, ['speed' => 50, 'stamina' => 55, 'defense' => 40, 'attack' => 55]],
            ['Takeshi', 'Kato', 12, 'Forward', 2500, ['speed' => 45, 'stamina' => 50, 'defense' => 35, 'attack' => 40]],
            ['Shingo', 'aio', 12, 'Midfielder', 4000, ['speed' => 90, 'stamina' => 95, 'defense' => 40, 'attack' => 55]],
            ['Shingo', 'Takada', 12, 'Midfielder', 8000, ['speed' => 67, 'stamina' => 70, 'defense' => 60, 'attack' => 68]],
            ['Noboru', 'Sawaki', 13, 'Defender', 8500, ['speed' => 60, 'stamina' => 75, 'defense' => 80, 'attack' => 58]],
            ['Hanji', 'Urabe', 12, 'Defender', 7000, ['speed' => 62, 'stamina' => 68, 'defense' => 77, 'attack' => 55]],
            ['Kazuo', 'Tachibana', 13, 'Forward', 9500, ['speed' => 75, 'stamina' => 75, 'defense' => 58, 'attack' => 80]],
            ['Masao', 'Tachibana', 13, 'Forward', 9500, ['speed' => 75, 'stamina' => 75, 'defense' => 58, 'attack' => 80]],
            ['Makoto', 'Soda', 13, 'Defender', 10000, ['speed' => 68, 'stamina' => 82, 'defense' => 85, 'attack' => 60]],
            ['Yuzo', 'Nakazato', 13, 'Midfielder', 7800, ['speed' => 65, 'stamina' => 73, 'defense' => 65, 'attack' => 66]],
            ['Shun', 'Nitta', 11, 'Forward', 9300, ['speed' => 80, 'stamina' => 78, 'defense' => 55, 'attack' => 82]],
            ['Mitsuru', 'Sano', 13, 'Forward', 9100, ['speed' => 82, 'stamina' => 76, 'defense' => 53, 'attack' => 81]],
            ['Ryoma', 'Hino', 13, 'Forward', 9600, ['speed' => 77, 'stamina' => 80, 'defense' => 57, 'attack' => 84]],
            ['Kazumasa', 'Oda', 12, 'Forward', 8500, ['speed' => 73, 'stamina' => 76, 'defense' => 55, 'attack' => 79]],
            ['Daichi', 'Kakeru', 12, 'Midfielder', 7800, ['speed' => 70, 'stamina' => 75, 'defense' => 65, 'attack' => 72]],
            ['Yayoi', 'Aoba', 12, 'Defender', 7200, ['speed' => 66, 'stamina' => 74, 'defense' => 78, 'attack' => 60]],
            ['Kenichi', 'Iwami', 12, 'Goalkeeper', 7400, ['speed' => 62, 'stamina' => 80, 'defense' => 84, 'attack' => 52]],
            ['Kuniharu', 'Uematsu', 12, 'Defender', 7100, ['speed' => 61, 'stamina' => 70, 'defense' => 79, 'attack' => 57]],
            ['Katsutoshi', 'Hasegawa', 13, 'Midfielder', 8000, ['speed' => 67, 'stamina' => 76, 'defense' => 68, 'attack' => 70]],
        ];

        foreach ($players as $player) {
            DB::table('players')->insert([
                'firstname' => $player[0],
                'lastname' => $player[1],
                'age' => $player[2],
                'position' => $player[3],
                'cost' => $player[4],
                'stats' => json_encode($player[5])
            ]);
        }
    }
}

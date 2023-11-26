<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('teams')->insert([
            ['name' => 'Nankatsu SC', 'budget' => 100000, 'wins' => 0, 'draws' => 0, 'losses' => 0],
            ['name' => 'Toho Academy', 'budget' => 100000, 'wins' => 0, 'draws' => 0, 'losses' => 0],
            ['name' => 'Hanawa FC', 'budget' => 100000, 'wins' => 0, 'draws' => 0, 'losses' => 0],
            ['name' => 'Furano FC', 'budget' => 100000, 'wins' => 0, 'draws' => 0, 'losses' => 0],
            ['name' => 'Otomo FC', 'budget' => 100000, 'wins' => 0, 'draws' => 0, 'losses' => 0],
            ['name' => 'Azumaichi FC', 'budget' => 100000, 'wins' => 0, 'draws' => 0, 'losses' => 0],
            ['name' => 'Musashi FC', 'budget' => 100000, 'wins' => 0, 'draws' => 0, 'losses' => 0],
            ['name' => 'Shutetsu FC', 'budget' => 100000, 'wins' => 0, 'draws' => 0, 'losses' => 0],
            ['name' => 'Meiwa FC', 'budget' => 100000, 'wins' => 0, 'draws' => 0, 'losses' => 0],
            ['name' => 'Nakahara FC', 'budget' => 100000, 'wins' => 0, 'draws' => 0, 'losses' => 0],
            ['name' => 'Shimizu FC', 'budget' => 100000, 'wins' => 0, 'draws' => 0, 'losses' => 0],
            ['name' => 'Shimada FC', 'budget' => 100000, 'wins' => 0, 'draws' => 0, 'losses' => 0],
        ]);
    }
}

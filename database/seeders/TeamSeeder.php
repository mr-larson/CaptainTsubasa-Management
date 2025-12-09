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
            [
                'name' => 'Nankatsu',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Nankatsu ES is an elementary school team from Nankatsu City, Shizuoka Prefecture.'
            ],
            [
                'name' => 'Toho',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Toho Academy MS is a middle school team from Setagaya, Tokyo.'
            ],
            [
                'name' => 'Hanawa',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Hanawa SS is an elementary school team from Kazuno City, Akita Prefecture.'
            ],
            [
                'name' => 'Furano',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Furano ES is an elementary school team from Furano City, Hokkaido.'
            ],
            [
                'name' => 'Otomo',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Otomo MS is a middle school team from Shizuoka Prefecture.'
            ],
            [
                'name' => 'Azumaichi',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Azumaichi MS is a middle school team from Toyono District, Osaka Prefecture.'
            ],
            [
                'name' => 'Musashi',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Musashi FC is a selection team of elementary school players from Musashino City, Tokyo, consisting of elite selected among over 1000 juvenile players.'
            ],
            [
                'name' => 'Shutetsu',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Shutetsu ES is an elementary school team from Nankatsu City, Shizuoka Prefecture. The football club has a high number of members, which allows Shutetsu to have a B team.'
            ],
            [
                'name' => 'Meiwa',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Meiwa FC is an elementary school team from Misato City, Saitama Prefecture.'
            ],
            [
                'name' => 'Hirado',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Hirado MS is a middle school team from Hirado City, Nagasaki Prefecture.'
            ],
            [
                'name' => 'Naniwa',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Naniwa FC is an elementary school team from Namba, Osaka.'
            ],
            [
                'name' => 'Minawi',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Minamiuwa MS is a middle school team from Minamiuwa District, Ehime Prefecture.'
            ],
            [
                'name' => 'Nakahara',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Nakahara FC is an elementary school team from Gifu Prefecture.'
            ],
            [
                'name' => 'Shimizu',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Shimizu FC is an elementary school team from Shimizu City, Shizuoka Prefecture.'
            ],
            [
                'name' => 'Shimada',
                'budget' => 10000,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'description' => 'Shimada ES is an elementary school team from Shimada City, Shizuoka Prefecture.'
            ],
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute `players.national_eligible` : le joueur peut-il être convoqué en sélection nationale ?
 * Vrai par défaut. Pour le Japon, seuls 42 personnages Captain Tsubasa de premier plan restent éligibles ;
 * les autres joueurs de clubs, figurants qui comblent les effectifs, sont exclus du vivier national
 * (ils restent jouables dans leurs clubs). Liste figée, jamais déduite d'un seeder évolutif.
 */
return new class extends Migration
{
    /** Crée la colonne puis exclut les figurants japonais de la sélection nationale. */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->boolean('national_eligible')->default(true)->after('nationality');
        });

        $eligible = [
            'Tsubasa Ozora',
            'Taro Misaki',
            'Yuzo Morisaki',
            'Ryo Ishizaki',
            'Genzo Wakabayashi',
            'Mamoru Izawa',
            'Hajime Taki',
            'Teppei Kisugi',
            'Shingo Takasugi',
            'Ken Wakashimazu',
            'Kojiro Hyuga',
            'Takeshi Sawada',
            'Tsuneo Takashima',
            'Kazuki Sorimachi',
            'Masao Tachibana',
            'Kazuo Tachibana',
            'Yuichiro Daimaru',
            'Hanji Urabe',
            'Shun Nitta',
            'Koji Nishio',
            'Masao Nakayama',
            'Takeshi Kishida',
            'Shinishi Sakamoto',
            'Motoharu Nagano',
            'Yuichi Suenaga',
            'Hikaru Matsuyama',
            'Kazumasa Oda',
            'Masanori Kato',
            'Jun Misugi',
            'Minoru Honma',
            'Hiroshi Jito',
            'Mitsuru Sano',
            'Makoto Soda',
            'Tetsuo Ishida',
            'Shingo Aoi',
            'Taichi Nakanishi',
            'Junichi Nagasaki',
            'Kazumasa Kato',
            'Michel Yamada',
            'Nobuyuki Yumikura',
            'Takashi Sugimoto',
            'Toshiya Okano',
        ];

        DB::table('players')->where('origin', 'captain_tsubasa')->where('nationality', 'Japon')
            ->orderBy('id')->get(['id', 'firstname', 'lastname'])
            ->each(function ($player) use ($eligible) {
                if (! in_array(trim($player->firstname.' '.$player->lastname), $eligible, true)) {
                    DB::table('players')->where('id', $player->id)->update(['national_eligible' => false]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('national_eligible');
        });
    }
};

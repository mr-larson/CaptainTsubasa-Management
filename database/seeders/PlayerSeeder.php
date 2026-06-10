<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\CalculatesWeeklyCost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlayerSeeder extends Seeder
{
    use CalculatesWeeklyCost;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // On remet la table à zéro avant de reseed
        Schema::disableForeignKeyConstraints();
        DB::table('players')->truncate();
        Schema::enableForeignKeyConstraints();

        /**
         * CONFIG IMAGES
         * - Source: mets tes PNG ici (dans ton repo)
         *   database/seeders/assets/players/firstname-lastname.png
         * - Destination: le seeder copie vers
         *   storage/app/public/players/firstname-lastname.png
         *
         * Ensuite, côté front: /storage/players/firstname-lastname.png
         */
        $imagesSourceDir = database_path('seeders/assets/players'); // <--- dossier à créer
        $storageDisk = Storage::disk('public');
        $storageDir = 'players';

        // (optionnel) crée le dossier destination si pas présent
        if (!$storageDisk->exists($storageDir)) {
            $storageDisk->makeDirectory($storageDir);
        }


        $specialMovesByPlayerSlug = [

            // =======================
            // NANKATSU
            // =======================

            'tsubasa-ozora' => [[
                'key'         => 'tsubasa_feuille_morte',
                'mode'        => 'attack',
                'label'       => 'Tir de la feuille morte',
                'short_label' => 'Feuille morte',
                'cooldown'    => 2,
                'base_action' => 'shot',
                'description' => 'Un tir flottant et imprévisible qui retombe brusquement devant le gardien.',
            ]],

            'taro-misaki' => [[
                'key'         => 'misaki_magicien_terrain',
                'mode'        => 'attack',
                'label'       => 'Passe du magicien',
                'short_label' => 'Magicien',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Une passe créative et millimétrée qui casse les lignes défensives.',
            ]],

            'ryo-ishizaki' => [[
                'key'         => 'ishizaki_face_block',
                'mode'        => 'defense',
                'label'       => 'Face block',
                'short_label' => 'Face block',
                'cooldown'    => 3,
                'base_action' => 'block',
                'description' => 'Ishizaki se jette tête la première pour bloquer une frappe à bout portant.',
            ]],

            // =======================
            // SHUTETSU
            // =======================

            'genzo-wakabayashi' => [[
                'key'         => 'wakabayashi_sggk_catch',
                'mode'        => 'defense',
                'label'       => 'Arrêt SGGK',
                'short_label' => 'SGGK',
                'cooldown'    => 2,
                'base_action' => 'hand_save',
                'description' => 'Un arrêt réflexe parfait du Super Great Goalkeeper, même sur des tirs surpuissants.',
            ]],

            'shingo-takasugi' => [[
                'key'         => 'takasugi_iron_wall',
                'mode'        => 'defense',
                'label'       => 'Mur de Shutetsu',
                'short_label' => 'Mur',
                'cooldown'    => 2,
                'base_action' => 'tackle',
                'description' => 'Un tacle défensif puissant et parfaitement maîtrisé qui stoppe net l’attaquant.',
            ]],

            'mamoru-izawa' => [[
                'key'         => 'izawa_midfield_maestro',
                'mode'        => 'attack',
                'label'       => 'Maestro de Shutetsu',
                'short_label' => 'Maestro',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Une ouverture lumineuse qui met un coéquipier en position idéale.',
            ]],

            'hajime-taki' => [[
                'key'         => 'taki_speed_dribble',
                'mode'        => 'attack',
                'label'       => 'Dribble supersonique',
                'short_label' => 'Supersonique',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Taki accélère brusquement et déborde son vis-à-vis grâce à sa vitesse.',
            ]],

            'teppei-kisugi' => [[
                'key'         => 'kisugi_clinical_finish',
                'mode'        => 'attack',
                'label'       => 'Frappe clinique',
                'short_label' => 'Finisseur',
                'cooldown'    => 2,
                'base_action' => 'shot',
                'description' => 'Une frappe précise placée dans le petit filet après un appel intelligent.',
            ]],

            // =======================
            // TOHO
            // =======================

            'kojiro-hyuga' => [[
                'key'         => 'hyuga_tir_du_tigre',
                'mode'        => 'attack',
                'label'       => 'Tir du Tigre',
                'short_label' => 'Tigre',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Un tir ultra puissant frappé de plein fouet, qui vise à traverser le gardien.',
            ]],

            'ken-wakashimazu' => [[
                'key'         => 'wakashimazu_kungfu_save',
                'mode'        => 'defense',
                'label'       => 'Arrêt karatéka',
                'short_label' => 'Karaté',
                'cooldown'    => 2,
                'base_action' => 'punch_save',
                'description' => 'Un arrêt acrobatique utilisant des réflexes de karaté pour repousser le ballon.',
            ]],

            'takeshi-sawada' => [[
                'key'         => 'sawada_tohos_brain',
                'mode'        => 'attack',
                'label'       => 'Cerveau de Toho',
                'short_label' => 'Tacticien',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Une passe stratégique qui lance une action dangereuse autour de Hyuga.',
            ]],

            'kazuki-sorimachi' => [[
                'key'         => 'sorimachi_poacher_finish',
                'mode'        => 'attack',
                'label'       => 'Renard de surface',
                'short_label' => 'Renard',
                'cooldown'    => 2,
                'base_action' => 'shot',
                'description' => 'Sorimachi profite d’un second ballon pour frapper instantanément dans la surface.',
            ]],

            // =======================
            // FURANO
            // =======================

            'hikaru-matsuyama' => [[
                'key'         => 'matsuyama_eagle_shot',
                'mode'        => 'attack',
                'label'       => 'Tir de l’Aigle',
                'short_label' => 'Aigle',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Un tir puissant frappé à moyenne distance, symbole de son leadership.',
            ]],

            // =======================
            // MUSASHI
            // =======================
            'jun-misugi' => [
                [
                    'key'         => 'misugi_field_prince',
                    'mode'        => 'attack',
                    'label'       => 'Prince du terrain',
                    'short_label' => 'Prince',
                    'cooldown'    => 3,
                    'base_action' => 'dribble',
                    'description' => 'Une action où Misugi dicte le tempo et délivre une passe décisive parfaite.',
                ],
                [
                    'key'         => 'misugi_elegant_block',
                    'mode'        => 'defense',
                    'label'       => 'Mur Miraculeux',
                    'short_label' => 'Miracle',
                    'cooldown'    => 3,
                    'base_action' => 'intercept',
                    'description' => 'Misugi anticipe parfaitement la passe adverse et intercepte le ballon avec une élégance inégalée.',
                ],
            ],

            // =======================
            // HANAWA
            // =======================

            'masao-tachibana' => [[
                'key'         => 'tachibana_acrobatic_twin',
                'mode'        => 'attack',
                'label'       => 'Acrobatie aérienne',
                'short_label' => 'Acrobatie',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Un tir acrobatique après une combinaison aérienne avec son frère.',
            ]],

            'kazuo-tachibana' => [[
                'key'         => 'tachibana_twin_jump',
                'mode'        => 'attack',
                'label'       => 'Saut combiné',
                'short_label' => 'Twin',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Un saut coordonné avec Masao pour reprendre un centre en pleine extension.',
            ]],

            // =======================
            // HIRADO
            // =======================

            'hiroshi-jito' => [[
                'key'         => 'jito_mountain_tackle',
                'mode'        => 'defense',
                'label'       => 'Tacle de la montagne',
                'short_label' => 'Montagne',
                'cooldown'    => 3,
                'base_action' => 'tackle',
                'description' => 'Un tacle d’une puissance colossale qui stoppe physiquement l’attaquant.',
            ]],

            'mitsuru-sano' => [[
                'key'         => 'sano_quick_counter',
                'mode'        => 'attack',
                'label'       => 'Contre éclair',
                'short_label' => 'Contre',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Sano déclenche un contre rapide en éliminant son vis-à-vis en un geste.',
            ]],

            // =======================
            // OTOMO
            // =======================

            'shun-nitta' => [[
                'key'         => 'nitta_falcon_volley',
                'mode'        => 'attack',
                'label'       => 'Volée du Faucon',
                'short_label' => 'Faucon',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Une volée en mouvement après un appel en profondeur, digne d’un faucon.',
            ]],

            'hanji-urabe' => [[
                'key'         => 'urabe_rough_press',
                'mode'        => 'defense',
                'label'       => 'Pressing agressif',
                'short_label' => 'Pressing',
                'cooldown'    => 2,
                'base_action' => 'tackle',
                'description' => 'Urabe harcèle physiquement le porteur de balle jusqu’à lui prendre le ballon.',
            ]],

            // =======================
            // AZUMA-ICHI (SODA)
            // =======================

            'makoto-soda' => [[
                'key'         => 'soda_kazaguruma_tackle',
                'mode'        => 'defense',
                'label'       => 'Tacle moulinet',
                'short_label' => 'Moulinet',
                'cooldown'    => 3,
                'base_action' => 'tackle',
                'description' => 'Un tacle violent en rotation qui fauche tout sur son passage.',
            ]],

            // =======================
            // REAL SEVEN
            // =======================

            'ryoma-hino' => [[
                'key'         => 'hino_fire_shot',
                'mode'        => 'attack',
                'label'       => 'Tir de Feu',
                'short_label' => 'Feu',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Un tir surpuissant et brûlant qui semble enflammer le ballon.',
            ]],

            // =======================
            // NAKAHARA (AOI)
            // =======================

            'shingo-aoi' => [[
                'key'         => 'aoi_euro_step',
                'mode'        => 'attack',
                'label'       => 'Dribble européen',
                'short_label' => 'Euro',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Un dribble inspiré du football européen pour éliminer son adversaire direct.',
            ]],

            // =======================
            // NANIWA (NAKANISHI)
            // =======================

            'taichi-nakanishi' => [[
                'key'         => 'nakanishi_giant_wall',
                'mode'        => 'defense',
                'label'       => 'Mur géant',
                'short_label' => 'Géant',
                'cooldown'    => 2,
                'base_action' => 'hand_save',
                'description' => 'Le gardien géant ferme entièrement l’axe et bloque un tir à bout portant.',
            ]],
        ];

        $players = [
            // Nankatsu
            [
                'Yuzo', 'Morisaki', 12, 'Goalkeeper', 34,
                [
                    'speed' => 60, 'stamina' => 80, 'defense' => 32, 'attack' => 18,
                    'shot' => 16, 'pass' => 18, 'dribble' => 16,
                    'block' => 18, 'intercept' => 20, 'tackle' => 18,
                    'hand_save' => 27, 'punch_save' => 24
                ],
                'Gardien titulaire de Nankatsu. Sérieux et travailleur, Morisaki compense son manque de talent naturel par une grande endurance et une forte discipline.'
            ],

            [
                'Masato', 'Nakazato', 12, 'Defender', 24,
                [
                    'speed' => 45, 'stamina' => 55, 'defense' => 22, 'attack' => 18,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 20, 'intercept' => 18, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur discret de Nankatsu, Nakazato joue un rôle simple et efficace. Il assure le marquage et soutient la ligne arrière sans prendre de risques.'
            ],

            [
                'Ryo', 'Ishizaki', 12, 'Defender', 42,
                [
                    'speed' => 65, 'stamina' => 75, 'defense' => 31, 'attack' => 22,
                    'shot' => 18, 'pass' => 23, 'dribble' => 20,
                    'block' => 25, 'intercept' => 26, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur emblématique de Nankatsu, Ishizaki est connu pour son courage, ses interventions désespérées et son célèbre « face block ». Son mental compense largement ses limites techniques.'
            ],

            [
                'Hiroshi', 'Nagano', 12, 'Defender', 23,
                [
                    'speed' => 43, 'stamina' => 58, 'defense' => 23, 'attack' => 17,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 21, 'intercept' => 19, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur de soutien, Nagano fait partie des joueurs de rotation de Nankatsu. Il applique les consignes et renforce la défense collective.'
            ],

            [
                'Manabu', 'Okawa', 12, 'Defender', 23,
                [
                    'speed' => 44, 'stamina' => 57, 'defense' => 22, 'attack' => 17,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 20, 'intercept' => 19, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Joueur discipliné et peu spectaculaire, Okawa apporte de la stabilité défensive et participe à l’équilibre de l’équipe.'
            ],

            [
                'Susumu', 'Sakurai', 12, 'Defender', 225,
                [
                    'speed' => 42, 'stamina' => 56, 'defense' => 21, 'attack' => 16,
                    'shot' => 15, 'pass' => 16, 'dribble' => 15,
                    'block' => 20, 'intercept' => 18, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur de complément, Sakurai joue un rôle modeste mais fiable dans la structure défensive de Nankatsu.'
            ],

            [
                'Kenichi', 'Iwami', 12, 'Midfielder', 240,
                [
                    'speed' => 48, 'stamina' => 59, 'defense' => 19, 'attack' => 21,
                    'shot' => 19, 'pass' => 21, 'dribble' => 19,
                    'block' => 18, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Iwami joue un rôle discret mais essentiel dans la circulation du ballon.'
            ],

            [
                'Taro', 'Misaki', 12, 'Midfielder', 475,
                [
                    'speed' => 75, 'stamina' => 80, 'defense' => 29, 'attack' => 37,
                    'shot' => 28, 'pass' => 30, 'dribble' => 28,
                    'block' => 19, 'intercept' => 24, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Surnommé le « magicien du terrain », Misaki est le partenaire idéal de Tsubasa. Créatif, élégant et altruiste, il excelle dans le jeu collectif.'
            ],

            [
                'Tsuyoshi', 'Oda', 12, 'Midfielder', 245,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 24, 'attack' => 20,
                    'shot' => 20, 'pass' => 22, 'dribble' => 20,
                    'block' => 20, 'intercept' => 23, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain polyvalent, Oda participe autant à la récupération qu’à la construction du jeu.'
            ],

            [
                'Yutaka', 'Murashige', 12, 'Forward', 250,
                [
                    'speed' => 49, 'stamina' => 60, 'defense' => 16, 'attack' => 28,
                    'shot' => 25, 'pass' => 20, 'dribble' => 22,
                    'block' => 16, 'intercept' => 16, 'tackle' => 16,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant volontaire, Murashige apporte une présence offensive et n’hésite pas à tenter sa chance face au but.'
            ],

            [
                'Tsubasa', 'Ozora', 12, 'Forward', 500,
                [
                    'speed' => 78, 'stamina' => 85, 'defense' => 30, 'attack' => 39,
                    'shot' => 30, 'pass' => 29, 'dribble' => 30,
                    'block' => 20, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Prodige du football japonais, Tsubasa est le cœur et l’âme de Nankatsu. Doté d’une vision exceptionnelle et d’une technique complète, il élève le niveau de toute l’équipe.'
            ],

            [
                'Shota', 'Minowa', 12, 'Midfielder', 235,
                [
                    'speed' => 46, 'stamina' => 57, 'defense' => 18, 'attack' => 30,
                    'shot' => 24, 'pass' => 22, 'dribble' => 23,
                    'block' => 17, 'intercept' => 18, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu offensif secondaire, Minowa soutient l’attaque et profite des espaces créés par Tsubasa et Misaki.'
            ],

            [
                'Akira', 'Tsuboi', 12, 'Goalkeeper', 250,
                [
                    'speed' => 40, 'stamina' => 60, 'defense' => 24, 'attack' => 16,
                    'shot' => 15, 'pass' => 16, 'dribble' => 15,
                    'block' => 16, 'intercept' => 16, 'tackle' => 15,
                    'hand_save' => 21, 'punch_save' => 18
                ],
                'Gardien remplaçant de Nankatsu, Tsuboi manque d’expérience mais reste fiable lors des rotations.'
            ],

            // Shutetsu
            [
                'Genzo', 'Wakabayashi', 12, 'Goalkeeper', 500,
                [
                    'speed' => 70, 'stamina' => 70, 'defense' => 39, 'attack' => 18,
                    'shot' => 16, 'pass' => 18, 'dribble' => 16,
                    'block' => 22, 'intercept' => 24, 'tackle' => 18,
                    'hand_save' => 30, 'punch_save' => 29
                ],
                'Surnommé le « SGGK » (Super Great Goalkeeper), Wakabayashi est le gardien prodige formé en Allemagne. Pilier de Shutetsu, il inspire le respect et stabilise toute l’équipe par son autorité et ses arrêts décisifs.'
            ],

            [
                'Kenta', 'Shimada', 12, 'Defender', 250,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 26, 'attack' => 18,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 22, 'intercept' => 20, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur appliqué de Shutetsu, Shimada joue un football simple et physique. Il se concentre sur le marquage et la protection de la zone devant Wakabayashi.'
            ],

            [
                'Shingo', 'Takasugi', 12, 'Defender', 375,
                [
                    'speed' => 64, 'stamina' => 70, 'defense' => 30, 'attack' => 20,
                    'shot' => 18, 'pass' => 20, 'dribble' => 17,
                    'block' => 25, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur fiable et rugueux de Shutetsu, Takasugi est l’un des cadres de la ligne arrière. Solide dans les duels et bon dans le timing, il sécurise l’équipe dans les matchs importants.'
            ],

            [
                'Wanatabe', 'Matsumo', 12, 'Defender', 225,
                [
                    'speed' => 48, 'stamina' => 58, 'defense' => 24, 'attack' => 17,
                    'shot' => 16, 'pass' => 16, 'dribble' => 15,
                    'block' => 21, 'intercept' => 19, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur de rotation, Matsumo se distingue surtout par sa rigueur. Il joue bas, ferme les angles et assure les couvertures sans chercher la prise de risque.'
            ],

            [
                'Kohei', 'Nakamoto', 12, 'Defender', 240,
                [
                    'speed' => 49, 'stamina' => 59, 'defense' => 25, 'attack' => 17,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 22, 'intercept' => 20, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur travailleur, Nakamoto complète la défense de Shutetsu par son engagement et son sens du placement. Il aide à conserver un bloc compact devant son gardien.'
            ],

            [
                'Yoshioka', 'Kurata', 12, 'Midfielder', 260,
                [
                    'speed' => 52, 'stamina' => 63, 'defense' => 21, 'attack' => 23,
                    'shot' => 20, 'pass' => 22, 'dribble' => 21,
                    'block' => 18, 'intercept' => 19, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain polyvalent, Kurata assure la transition défense-attaque. Il oriente le jeu de manière simple et soutient le pressing au milieu.'
            ],

            [
                'Akiyoshi', 'Osaki', 12, 'Midfielder', 270,
                [
                    'speed' => 54, 'stamina' => 65, 'defense' => 22, 'attack' => 24,
                    'shot' => 21, 'pass' => 23, 'dribble' => 22,
                    'block' => 18, 'intercept' => 19, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Osaki apporte de l’intensité et une bonne activité à Shutetsu. Il participe au jeu collectif et se projette quand l’équipe prend l’avantage.'
            ],

            [
                'Mamoru', 'Izawa', 12, 'Midfielder', 450,
                [
                    'speed' => 68, 'stamina' => 70, 'defense' => 28, 'attack' => 32,
                    'shot' => 25, 'pass' => 26, 'dribble' => 22,
                    'block' => 20, 'intercept' => 23, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Chef d’orchestre de Shutetsu au milieu, Izawa est un joueur intelligent et complet. Il sait temporiser, distribuer et servir de relais, ce qui rend l’équipe plus dangereuse à la récupération.'
            ],

            [
                'Kitajima', 'Inamura', 12, 'Midfielder', 290,
                [
                    'speed' => 55, 'stamina' => 67, 'defense' => 23, 'attack' => 25,
                    'shot' => 22, 'pass' => 24, 'dribble' => 23,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu dynamique, Inamura sert de soutien à Izawa et participe à la construction. Il propose des solutions courtes et se rend disponible entre les lignes.'
            ],

            [
                'Teppei', 'Kisugi', 13, 'Forward', 400,
                [
                    'speed' => 70, 'stamina' => 80, 'defense' => 16, 'attack' => 34,
                    'shot' => 26, 'pass' => 22, 'dribble' => 25,
                    'block' => 16, 'intercept' => 16, 'tackle' => 16,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Avant-centre de Shutetsu, Kisugi est un finisseur opportuniste. Il joue sur ses déplacements, attaque la profondeur et cherche à conclure rapidement dès qu’une brèche s’ouvre.'
            ],

            [
                'Hajime', 'Taki', 12, 'Forward', 400,
                [
                    'speed' => 80, 'stamina' => 75, 'defense' => 18, 'attack' => 33,
                    'shot' => 25, 'pass' => 22, 'dribble' => 26,
                    'block' => 16, 'intercept' => 17, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant très rapide, Taki est l’arme de Shutetsu en contre-attaque. Il mise sur la vitesse et le dribble pour déborder, provoquer et créer des situations de tir.'
            ],

            // Toho
            [
                'Ken', 'Wakashimazu', 13, 'Goalkeeper', 465,
                [
                    'speed' => 70, 'stamina' => 85, 'defense' => 36, 'attack' => 22,
                    'shot' => 22, 'pass' => 20, 'dribble' => 19,
                    'block' => 22, 'intercept' => 24, 'tackle' => 21,
                    'hand_save' => 28, 'punch_save' => 30
                ],
                'Gardien karatéka de Toho, Wakashimazu se distingue par ses réflexes explosifs et son style acrobatique. Capable de jouer gardien comme joueur de champ, il apporte une agressivité unique à l’équipe.'
            ],

            [
                'Kiyoshi', 'Furuta', 12, 'Defender', 250,
                [
                    'speed' => 60, 'stamina' => 68, 'defense' => 28, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 23, 'intercept' => 22, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur solide de Toho, Furuta joue un rôle strictement défensif. Il mise sur l’impact physique et le pressing pour soutenir le bloc derrière Hyuga.'
            ],

            [
                'Katsuji', 'Kawabe', 12, 'Defender', 260,
                [
                    'speed' => 62, 'stamina' => 70, 'defense' => 29, 'attack' => 22,
                    'shot' => 18, 'pass' => 20, 'dribble' => 18,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur rugueux, Kawabe incarne le style agressif de Toho. Il n’hésite pas à aller au duel pour casser le rythme adverse.'
            ],

            [
                'Tsuneo', 'Takashima', 12, 'Defender', 265,
                [
                    'speed' => 64, 'stamina' => 72, 'defense' => 30, 'attack' => 23,
                    'shot' => 19, 'pass' => 20, 'dribble' => 18,
                    'block' => 25, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Pilier de la défense de Toho, Takashima est l’un des joueurs les plus fiables derrière. Son sens du placement et sa dureté défensive sécurisent l’arrière-garde.'
            ],

            [
                'Hiroshi', 'Imai', 12, 'Defender', 255,
                [
                    'speed' => 60, 'stamina' => 66, 'defense' => 27, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 23, 'intercept' => 22, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur discipliné, Imai complète la ligne arrière de Toho par sa régularité et son engagement constant.'
            ],

            [
                'Hideto', 'Koike', 12, 'Midfielder', 265,
                [
                    'speed' => 62, 'stamina' => 70, 'defense' => 26, 'attack' => 22,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu polyvalent, Koike assure le lien entre défense et attaque. Il joue simple et soutient l’intensité imposée par Toho.'
            ],

            [
                'Yutaka', 'Matsuki', 12, 'Midfielder', 270,
                [
                    'speed' => 64, 'stamina' => 72, 'defense' => 27, 'attack' => 24,
                    'shot' => 22, 'pass' => 24, 'dribble' => 22,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain actif, Matsuki participe à la récupération et à la projection offensive. Il soutient la construction autour de Sawada.'
            ],

            [
                'Takeshi', 'Sawada', 11, 'Midfielder', 415,
                [
                    'speed' => 73, 'stamina' => 80, 'defense' => 28, 'attack' => 35,
                    'shot' => 25, 'pass' => 27, 'dribble' => 27,
                    'block' => 19, 'intercept' => 24, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Jeune stratège de Toho, Sawada est le cerveau de l’équipe. Malgré son âge, il se distingue par son intelligence de jeu et son excellente vision offensive.'
            ],

            [
                'Tadashi', 'Shimano', 12, 'Midfielder', 260,
                [
                    'speed' => 63, 'stamina' => 70, 'defense' => 26, 'attack' => 23,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Shimano renforce le pressing et aide à maintenir la domination physique de Toho au milieu.'
            ],

            [
                'Kojiro', 'Hyuga', 13, 'Forward', 500,
                [
                    'speed' => 78, 'stamina' => 85, 'defense' => 22, 'attack' => 42,
                    'shot' => 31, 'pass' => 20, 'dribble' => 27,
                    'block' => 18, 'intercept' => 20, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Avant-centre emblématique de Toho, Hyuga est un attaquant puissant et impitoyable. Son tir du tigre et son mental de compétiteur font de lui l’arme offensive principale de l’équipe.'
            ],

            [
                'Kazuki', 'Sorimachi', 12, 'Forward', 345,
                [
                    'speed' => 68, 'stamina' => 75, 'defense' => 20, 'attack' => 32,
                    'shot' => 26, 'pass' => 23, 'dribble' => 24,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant opportuniste, Sorimachi complète parfaitement Hyuga. Il profite des espaces créés par son capitaine pour se projeter et conclure.'
            ],

            // Furano
            [
                'Masanori', 'Kato', 12, 'Goalkeeper', 250,
                [
                    'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16,
                    'shot' => 15, 'pass' => 18, 'dribble' => 15,
                    'block' => 20, 'intercept' => 21, 'tackle' => 17,
                    'hand_save' => 25, 'punch_save' => 22
                ],
                'Gardien fiable de Furano, Kato n’est pas spectaculaire mais se distingue par sa constance. Il rassure sa défense par son placement et sa régularité.'
            ],

            [
                'Susumu', 'Honda', 12, 'Defender', 260,
                [
                    'speed' => 60, 'stamina' => 70, 'defense' => 29, 'attack' => 22,
                    'shot' => 18, 'pass' => 20, 'dribble' => 18,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur robuste de Furano, Honda incarne la solidité et l’endurance de l’équipe. Il excelle dans les duels et la récupération.'
            ],

            [
                'Tsuyoshi', 'Kondo', 12, 'Defender', 265,
                [
                    'speed' => 58, 'stamina' => 68, 'defense' => 28, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 23, 'intercept' => 22, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur discipliné, Kondo joue un rôle clé dans l’organisation défensive. Il ferme les espaces et soutient le pressing collectif.'
            ],

            [
                'Kentaro', 'Kamata', 12, 'Defender', 255,
                [
                    'speed' => 57, 'stamina' => 66, 'defense' => 27, 'attack' => 22,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 23, 'intercept' => 22, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur fiable, Kamata complète la ligne arrière par son sérieux et son engagement constant.'
            ],

            [
                'Hisashi', 'Matsuda', 12, 'Midfielder', 270,
                [
                    'speed' => 64, 'stamina' => 74, 'defense' => 27, 'attack' => 24,
                    'shot' => 22, 'pass' => 24, 'dribble' => 22,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain endurant, Matsuda participe à la récupération et à la projection offensive. Il soutient le rythme élevé imposé par Furano.'
            ],

            [
                'Haruo', 'Kaneda', 12, 'Midfielder', 275,
                [
                    'speed' => 56, 'stamina' => 65, 'defense' => 26, 'attack' => 21,
                    'shot' => 20, 'pass' => 22, 'dribble' => 20,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu travailleur, Kaneda joue un rôle d’équilibre entre défense et attaque. Il privilégie le collectif à la performance individuelle.'
            ],

            [
                'Koichi', 'Wakamatsu', 12, 'Midfielder', 265,
                [
                    'speed' => 55, 'stamina' => 64, 'defense' => 26, 'attack' => 21,
                    'shot' => 20, 'pass' => 22, 'dribble' => 20,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Wakamatsu assure la continuité du jeu et renforce la cohésion du bloc de Furano.'
            ],

            [
                'Hikaru', 'Matsuyama', 13, 'Midfielder', 475,
                [
                    'speed' => 75, 'stamina' => 87, 'defense' => 35, 'attack' => 36,
                    'shot' => 28, 'pass' => 25, 'dribble' => 25,
                    'block' => 25, 'intercept' => 26, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Capitaine de Furano, Matsuyama est un leader infatigable. Son endurance exceptionnelle, sa rigueur défensive et son mental font de lui le pilier de l’équipe.'
            ],

            [
                'Seiji', 'Nakagawa', 12, 'Forward', 270,
                [
                    'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 30,
                    'shot' => 25, 'pass' => 20, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant volontaire, Nakagawa mise sur le placement et le travail sans ballon pour créer des opportunités.'
            ],

            [
                'Kazumasa', 'Oda', 12, 'Forward', 315,
                [
                    'speed' => 70, 'stamina' => 73, 'defense' => 20, 'attack' => 32,
                    'shot' => 27, 'pass' => 21, 'dribble' => 24,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Avant-centre principal de Furano, Oda est un finisseur puissant qui profite du pressing et du volume de jeu de son équipe.'
            ],

            [
                'Shuichi', 'Yamamuro', 12, 'Forward', 260,
                [
                    'speed' => 60, 'stamina' => 67, 'defense' => 18, 'attack' => 30,
                    'shot' => 25, 'pass' => 20, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de rotation, Yamamuro complète l’attaque de Furano par son engagement et sa disponibilité dans la surface.'
            ],

            // Musashi
            [
                'Tsutomu', 'Moriyama', 12, 'Goalkeeper', 300,
                [
                    'speed' => 50, 'stamina' => 70, 'defense' => 30, 'attack' => 18,
                    'shot' => 16, 'pass' => 18, 'dribble' => 15,
                    'block' => 22, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 26, 'punch_save' => 23
                ],
                'Gardien sérieux de Musashi, Moriyama se distingue par son calme et son sens du placement. Il n’est pas spectaculaire, mais fiable dans un système défensif rigoureux.'
            ],

            [
                'Osamu', 'Kido', 12, 'Defender', 290,
                [
                    'speed' => 48, 'stamina' => 68, 'defense' => 29, 'attack' => 22,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 22, 'intercept' => 23, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur discipliné, Kido applique strictement les consignes tactiques de Musashi. Il privilégie l’anticipation et le jeu sans ballon.'
            ],

            [
                'Hiroshi', 'Mukai', 12, 'Defender', 280,
                [
                    'speed' => 46, 'stamina' => 66, 'defense' => 28, 'attack' => 21,
                    'shot' => 17, 'pass' => 18, 'dribble' => 16,
                    'block' => 23, 'intercept' => 22, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur appliqué, Mukai assure les couvertures et le maintien du bloc défensif. Il incarne la rigueur collective de Musashi.'
            ],

            [
                'Ryoichi', 'Sano', 12, 'Defender', 275,
                [
                    'speed' => 45, 'stamina' => 65, 'defense' => 27, 'attack' => 21,
                    'shot' => 17, 'pass' => 18, 'dribble' => 16,
                    'block' => 23, 'intercept' => 22, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur méthodique, Sano complète la ligne arrière par sa constance et son sens du placement.'
            ],

            [
                'Shinichi', 'Suzuki', 12, 'Midfielder', 295,
                [
                    'speed' => 49, 'stamina' => 69, 'defense' => 26, 'attack' => 24,
                    'shot' => 22, 'pass' => 24, 'dribble' => 22,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain équilibré, Suzuki assure la circulation du ballon et soutient le jeu collectif voulu par Musashi.'
            ],

            [
                'Kensaku', 'Yoshida', 12, 'Midfielder', 285,
                [
                    'speed' => 47, 'stamina' => 67, 'defense' => 25, 'attack' => 23,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Yoshida privilégie la sécurité et l’équilibre. Il participe à la construction sans prendre de risques.'
            ],

            [
                'Shota', 'Inoue', 12, 'Midfielder', 290,
                [
                    'speed' => 48, 'stamina' => 68, 'defense' => 25, 'attack' => 23,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu travailleur, Inoue renforce la densité du milieu de terrain et applique strictement les schémas tactiques.'
            ],

            [
                'Jun', 'Misugi', 13, 'Midfielder', 500,
                [
                    'speed' => 83, 'stamina' => 50, 'defense' => 38, 'attack' => 38,
                    'shot' => 29, 'pass' => 29, 'dribble' => 29,
                    'block' => 25, 'intercept' => 25, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Surnommé le « Prince du terrain », Misugi est un génie tactique et technique. Freiné par une santé fragile, il compense par une intelligence de jeu exceptionnelle et une maîtrise totale du rythme.'
            ],

            [
                'Minoru', 'Honma', 12, 'Forward', 350,
                [
                    'speed' => 65, 'stamina' => 75, 'defense' => 20, 'attack' => 31,
                    'shot' => 26, 'pass' => 22, 'dribble' => 23,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant principal de Musashi, Honma se montre efficace dans la surface et sait exploiter les occasions créées par Misugi.'
            ],

            [
                'Akira', 'Ichinose', 12, 'Forward', 350,
                [
                    'speed' => 70, 'stamina' => 67, 'defense' => 18, 'attack' => 32,
                    'shot' => 27, 'pass' => 21, 'dribble' => 24,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant mobile, Ichinose apporte de la profondeur et des appels constants pour dynamiser l’attaque de Musashi.'
            ],

            [
                'Shinji', 'Sanada', 12, 'Forward', 350,
                [
                    'speed' => 65, 'stamina' => 72, 'defense' => 19, 'attack' => 30,
                    'shot' => 25, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de soutien, Sanada complète le trio offensif par son activité et son sens du placement.'
            ],

            // Hanawa
            [
                'Kimio', 'Yoshikura', 12, 'Goalkeeper', 225,
                [
                    'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16,
                    'shot' => 15, 'pass' => 18, 'dribble' => 15,
                    'block' => 21, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 25, 'punch_save' => 22
                ],
                'Gardien de Hanawa, Yoshikura est un portier sérieux et discipliné. Il s’appuie sur un bon placement et une défense organisée devant lui.'
            ],

            [
                'Masaru', 'Koda', 12, 'Defender', 235,
                [
                    'speed' => 60, 'stamina' => 67, 'defense' => 28, 'attack' => 22,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur solide, Koda participe au pressing collectif de Hanawa. Il joue dur et coupe les trajectoires pour préparer les contres.'
            ],

            [
                'Yusuaki', 'Murasawa', 12, 'Defender', 240,
                [
                    'speed' => 58, 'stamina' => 68, 'defense' => 29, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur rigoureux, Murasawa renforce la solidité de la ligne arrière. Il privilégie l’impact et l’anticipation.'
            ],

            [
                'Norio', 'Nakamura', 12, 'Defender', 230,
                [
                    'speed' => 57, 'stamina' => 66, 'defense' => 28, 'attack' => 22,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur discipliné, Nakamura assure la stabilité défensive et applique strictement les consignes tactiques.'
            ],

            [
                'Yuichiro', 'Daimaru', 12, 'Midfielder', 310,
                [
                    'speed' => 64, 'stamina' => 74, 'defense' => 30, 'attack' => 24,
                    'shot' => 22, 'pass' => 25, 'dribble' => 22,
                    'block' => 22, 'intercept' => 22, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu central de Hanawa, Daimaru est le cerveau de l’équipe. Il oriente le jeu et prépare les ballons destinés aux frères Tachibana.'
            ],

            [
                'Takayuki', 'Shiota', 12, 'Midfielder', 225,
                [
                    'speed' => 56, 'stamina' => 65, 'defense' => 26, 'attack' => 21,
                    'shot' => 20, 'pass' => 22, 'dribble' => 20,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Shiota privilégie le jeu simple et participe à l’équilibre collectif.'
            ],

            [
                'Nobuo', 'Aimoto', 12, 'Midfielder', 230,
                [
                    'speed' => 55, 'stamina' => 64, 'defense' => 26, 'attack' => 21,
                    'shot' => 20, 'pass' => 22, 'dribble' => 20,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu travailleur, Aimoto soutient la récupération et sécurise les transitions.'
            ],

            [
                'Hiroshi', 'Tamai', 12, 'Midfielder', 235,
                [
                    'speed' => 59, 'stamina' => 67, 'defense' => 27, 'attack' => 22,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu polyvalent, Tamai apporte de la continuité dans le jeu et soutient les phases offensives.'
            ],

            [
                'Yoshiharu', 'Ono', 12, 'Forward', 240,
                [
                    'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 26,
                    'shot' => 23, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de complément, Ono joue un rôle de soutien et profite des espaces créés par le jeu aérien.'
            ],

            [
                'Masao', 'Tachibana', 13, 'Forward', 425,
                [
                    'speed' => 75, 'stamina' => 75, 'defense' => 20, 'attack' => 38,
                    'shot' => 29, 'pass' => 28, 'dribble' => 26,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'L’un des célèbres frères Tachibana. Spécialiste du jeu aérien et des combinaisons acrobatiques, Masao est une menace constante dans la surface.'
            ],

            [
                'Kazuo', 'Tachibana', 13, 'Forward', 425,
                [
                    'speed' => 75, 'stamina' => 75, 'defense' => 20, 'attack' => 38,
                    'shot' => 29, 'pass' => 28, 'dribble' => 26,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Second des frères Tachibana, Kazuo forme avec Masao un duo redoutable. Leur synchronisation parfaite et leurs techniques aériennes font l’identité offensive de Hanawa.'
            ],

            // Azuma-ichi
            [
                'Ryota', 'Tsuji', 12, 'Goalkeeper', 225,
                [
                    'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16,
                    'shot' => 15, 'pass' => 18, 'dribble' => 15,
                    'block' => 21, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 25, 'punch_save' => 22
                ],
                'Gardien d’Azuma-ichi, Tsuji se distingue par son sérieux et son sang-froid. Il s’appuie sur une défense très physique pour sécuriser sa surface.'
            ],

            [
                'Junji', 'Yamada', 12, 'Defender', 235,
                [
                    'speed' => 60, 'stamina' => 67, 'defense' => 28, 'attack' => 22,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur engagé, Yamada applique un marquage serré et participe au pressing agressif caractéristique d’Azuma-ichi.'
            ],

            [
                'Makoto', 'Soda', 12, 'Defender', 430,
                [
                    'speed' => 70, 'stamina' => 85, 'defense' => 38, 'attack' => 24,
                    'shot' => 25, 'pass' => 24, 'dribble' => 20,
                    'block' => 28, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur emblématique d’Azuma-ichi, Soda est réputé pour sa dureté extrême et ses interventions violentes. Redoutable dans les duels, il impose un climat physique intense à chaque match.'
            ],

            [
                'Daigo', 'Sasaki', 12, 'Defender', 240,
                [
                    'speed' => 58, 'stamina' => 68, 'defense' => 29, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur solide, Sasaki complète parfaitement Soda en assurant les couvertures et la continuité défensive.'
            ],

            [
                'Tatsuya', 'Hayashi', 12, 'Midfielder', 230,
                [
                    'speed' => 57, 'stamina' => 66, 'defense' => 26, 'attack' => 22,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain polyvalent, Hayashi participe autant à la récupération qu’à la relance rapide.'
            ],

            [
                'Koji', 'Yoshida', 12, 'Midfielder', 225,
                [
                    'speed' => 56, 'stamina' => 65, 'defense' => 26, 'attack' => 21,
                    'shot' => 20, 'pass' => 22, 'dribble' => 20,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Yoshida privilégie le jeu simple et sécurise les transitions.'
            ],

            [
                'Yohei', 'Kuramochi', 12, 'Midfielder', 220,
                [
                    'speed' => 55, 'stamina' => 64, 'defense' => 25, 'attack' => 21,
                    'shot' => 20, 'pass' => 22, 'dribble' => 20,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu travailleur, Kuramochi apporte du volume de jeu et renforce le pressing collectif.'
            ],

            [
                'Toru', 'Nakai', 12, 'Midfielder', 235,
                [
                    'speed' => 59, 'stamina' => 67, 'defense' => 26, 'attack' => 22,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu polyvalent, Nakai assure la continuité du jeu et soutient les phases offensives.'
            ],

            [
                'Kazuyasu', 'Onodera', 12, 'Forward', 240,
                [
                    'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 26,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant opportuniste, Onodera cherche à exploiter les ballons récupérés haut par le pressing.'
            ],

            [
                'Mitsuru', 'Ide', 12, 'Forward', 230,
                [
                    'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 26,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de soutien, Ide complète l’attaque par sa disponibilité et son activité sans ballon.'
            ],

            [
                'Shohei', 'Mihashi', 12, 'Forward', 240,
                [
                    'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 26,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant travailleur, Mihashi participe au pressing et à l’occupation de la surface adverse.'
            ],

            // Hirado
            [
                'Akira', 'Hatakeyama', 12, 'Goalkeeper', 225,
                [
                    'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16,
                    'shot' => 15, 'pass' => 18, 'dribble' => 15,
                    'block' => 21, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 25, 'punch_save' => 22
                ],
                'Gardien de Hirado, Hatakeyama est un portier sérieux et discipliné. Il s’appuie sur une défense très physique menée par Jito pour tenir face aux attaques adverses.'
            ],

            [
                'Kazuaki', 'Soda', 12, 'Defender', 235,
                [
                    'speed' => 60, 'stamina' => 67, 'defense' => 28, 'attack' => 22,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur engagé de Hirado, Soda joue un football rude et direct. Il applique un marquage serré et participe au pressing agressif de l’équipe.'
            ],

            [
                'Toshio', 'Akizawa', 12, 'Defender', 240,
                [
                    'speed' => 58, 'stamina' => 68, 'defense' => 29, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur solide, Akizawa complète la ligne arrière en assurant les couvertures et en renforçant le marquage autour de Jito.'
            ],

            [
                'Hiroshi', 'Jito', 13, 'Defender', 440,
                [
                    'speed' => 65, 'stamina' => 85, 'defense' => 36, 'attack' => 26,
                    'shot' => 22, 'pass' => 24, 'dribble' => 23,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Capitaine et pilier de Hirado, Jito est un défenseur puissant et intimidant. Redoutable dans les duels, il impose sa présence physique et coupe les attaques par la force et l’anticipation.'
            ],

            [
                'Shinji', 'Noda', 12, 'Defender', 230,
                [
                    'speed' => 57, 'stamina' => 66, 'defense' => 26, 'attack' => 19,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 21, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur travailleur, Noda soutient la récupération et relance de manière simple. Il privilégie le collectif et l’équilibre.'
            ],

            [
                'Tsutomu', 'Nagaoka', 12, 'Defender', 225,
                [
                    'speed' => 56, 'stamina' => 65, 'defense' => 26, 'attack' => 21,
                    'shot' => 12, 'pass' => 22, 'dribble' => 19,
                    'block' => 24, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur, Nagaoka assure la continuité du jeu et renforce le bloc compact de Hirado.'
            ],

            [
                'Koji', 'Nakajo', 12, 'Midfielder', 220,
                [
                    'speed' => 55, 'stamina' => 64, 'defense' => 25, 'attack' => 21,
                    'shot' => 20, 'pass' => 22, 'dribble' => 20,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu discipliné, Nakajo joue simple et participe à la pression collective sans chercher la prise de risque.'
            ],

            [
                'Shinji', 'Morisue', 12, 'Midfielder', 235,
                [
                    'speed' => 59, 'stamina' => 67, 'defense' => 27, 'attack' => 22,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu polyvalent, Morisue aide à maintenir la densité au centre et soutient les transitions rapides.'
            ],

            [
                'Kazuo', 'Takeno', 12, 'Midfielder', 240,
                [
                    'speed' => 58, 'stamina' => 66, 'defense' => 27, 'attack' => 23,
                    'shot' => 22, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain actif, Takeno participe à la récupération et sert de relais pour amener le ballon vers Sano en attaque.'
            ],

            [
                'Katsumi', 'Himeji', 12, 'Forward', 230,
                [
                    'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 26,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de soutien, Himeji occupe la défense adverse et profite des seconds ballons dans un jeu direct.'
            ],

            [
                'Mitsuru', 'Sano', 13, 'Forward', 340,
                [
                    'speed' => 62, 'stamina' => 76, 'defense' => 20, 'attack' => 36,
                    'shot' => 28, 'pass' => 22, 'dribble' => 26,
                    'block' => 18, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant vedette de Hirado, Sano est un dribbleur rapide et technique. Il forme un duo redoutable avec Jito, profitant des récupérations musclées pour partir en contre et provoquer.'
            ],

            // Otomo
            [
                'Isamu', 'Ichijo', 12, 'Goalkeeper', 275,
                [
                    'speed' => 50, 'stamina' => 65, 'defense' => 29, 'attack' => 18,
                    'shot' => 16, 'pass' => 18, 'dribble' => 15,
                    'block' => 21, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 24, 'punch_save' => 22
                ],
                'Gardien d’Otomo, Ichijo est un portier sérieux qui mise sur le placement et la discipline défensive. Il s’appuie sur un bloc arrière très compact.'
            ],

            [
                'Masaki', 'Yoshikawa', 12, 'Defender', 285,
                [
                    'speed' => 52, 'stamina' => 67, 'defense' => 30, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur rigoureux, Yoshikawa applique un marquage strict et privilégie la sécurité défensive.'
            ],

            [
                'Koji', 'Nishio', 12, 'Defender', 350,
                [
                    'speed' => 55, 'stamina' => 70, 'defense' => 34, 'attack' => 22,
                    'shot' => 19, 'pass' => 22, 'dribble' => 19,
                    'block' => 26, 'intercept' => 27, 'tackle' => 27,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Pilier défensif d’Otomo, Nishio est un stoppeur puissant et constant. Il excelle dans les duels et la récupération.'
            ],

            [
                'Masao', 'Nakayama', 12, 'Defender', 350,
                [
                    'speed' => 55, 'stamina' => 70, 'defense' => 34, 'attack' => 22,
                    'shot' => 19, 'pass' => 22, 'dribble' => 19,
                    'block' => 27, 'intercept' => 26, 'tackle' => 27,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur central très solide, Nakayama forme avec Nishio un duo défensif difficile à contourner.'
            ],

            [
                'Kozo', 'Kawada', 12, 'Defender', 290,
                [
                    'speed' => 53, 'stamina' => 68, 'defense' => 31, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 25, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur appliqué, Kawada apporte de la densité et sécurise les côtés de la défense.'
            ],

            [
                'Toru', 'Hiraoka', 12, 'Midfielder', 280,
                [
                    'speed' => 51, 'stamina' => 66, 'defense' => 25, 'attack' => 23,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu travailleur, Hiraoka assure la transition entre défense et attaque de manière simple.'
            ],

            [
                'Takeshi', 'Kishida', 12, 'Midfielder', 350,
                [
                    'speed' => 55, 'stamina' => 70, 'defense' => 26, 'attack' => 26,
                    'shot' => 22, 'pass' => 25, 'dribble' => 23,
                    'block' => 20, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain complet, Kishida participe à la récupération et à la création, apportant de l’équilibre au collectif.'
            ],

            [
                'Hanji', 'Urabe', 12, 'Midfielder', 400,
                [
                    'speed' => 60, 'stamina' => 75, 'defense' => 30, 'attack' => 30,
                    'shot' => 24, 'pass' => 25, 'dribble' => 25,
                    'block' => 22, 'intercept' => 25, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Leader du milieu d’Otomo, Urabe est un joueur combatif et complet. Il impose le rythme et n’hésite pas à provoquer physiquement.'
            ],

            [
                'Shingo', 'Tadami', 12, 'Midfielder', 290,
                [
                    'speed' => 53, 'stamina' => 68, 'defense' => 25, 'attack' => 25,
                    'shot' => 23, 'pass' => 24, 'dribble' => 22,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu offensif secondaire, Tadami soutient les phases de projection et cherche à alimenter l’attaque.'
            ],

            [
                'Akio', 'Nakao', 12, 'Forward', 300,
                [
                    'speed' => 55, 'stamina' => 70, 'defense' => 18, 'attack' => 31,
                    'shot' => 26, 'pass' => 21, 'dribble' => 23,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant travailleur, Nakao joue dos au but et crée des espaces pour son partenaire d’attaque.'
            ],

            [
                'Shun', 'Nitta', 12, 'Forward', 475,
                [
                    'speed' => 70, 'stamina' => 75, 'defense' => 18, 'attack' => 39,
                    'shot' => 29, 'pass' => 23, 'dribble' => 26,
                    'block' => 18, 'intercept' => 22, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Star offensive d’Otomo, Nitta est un attaquant explosif et opportuniste. Sa vitesse et son sens du but font de lui la principale menace de l’équipe.'
            ],

            // Meiwa
            [
                'Tetsuji', 'Murasawa', 12, 'Goalkeeper', 225,
                [
                    'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16,
                    'shot' => 15, 'pass' => 18, 'dribble' => 15,
                    'block' => 21, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 25, 'punch_save' => 22
                ],
                'Gardien de Meiwa, Murasawa est un portier sérieux et appliqué. Il mise sur son placement et la solidité de son bloc défensif.'
            ],

            [
                'Keiji', 'Kawagoe', 12, 'Defender', 230,
                [
                    'speed' => 60, 'stamina' => 68, 'defense' => 28, 'attack' => 20,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur discipliné, Kawagoe applique les consignes tactiques et sécurise les duels sur son côté.'
            ],

            [
                'Hiroshi', 'Ishii', 12, 'Defender', 235,
                [
                    'speed' => 58, 'stamina' => 66, 'defense' => 29, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur solide, Ishii privilégie l’anticipation et le jeu simple pour maintenir l’équilibre défensif.'
            ],

            [
                'Toshiyuki', 'Takagi', 12, 'Defender', 240,
                [
                    'speed' => 57, 'stamina' => 67, 'defense' => 29, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 25, 'intercept' => 24, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur rigoureux, Takagi renforce la solidité de la ligne arrière et ferme les espaces.'
            ],

            [
                'Motoharu', 'Nagano', 12, 'Defender', 325,
                [
                    'speed' => 65, 'stamina' => 69, 'defense' => 32, 'attack' => 22,
                    'shot' => 19, 'pass' => 20, 'dribble' => 18,
                    'block' => 26, 'intercept' => 25, 'tackle' => 27,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Pilier défensif de Meiwa, Nagano est un stoppeur puissant et constant. Il incarne la solidité et la rigueur de l’équipe.'
            ],

            [
                'Shinishi', 'Sakamoto', 12, 'Midfielder', 315,
                [
                    'speed' => 64, 'stamina' => 70, 'defense' => 30, 'attack' => 28,
                    'shot' => 25, 'pass' => 25, 'dribble' => 24,
                    'block' => 22, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu central complet, Sakamoto est le moteur du jeu de Meiwa. Il récupère, distribue et se projette avec intelligence.'
            ],

            [
                'Kuniaki', 'Narita', 12, 'Midfielder', 310,
                [
                    'speed' => 63, 'stamina' => 69, 'defense' => 29, 'attack' => 27,
                    'shot' => 24, 'pass' => 25, 'dribble' => 24,
                    'block' => 22, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu polyvalent, Narita soutient la construction du jeu et participe activement au pressing.'
            ],

            [
                'Hiromichi', 'Hori', 12, 'Midfielder', 310,
                [
                    'speed' => 63, 'stamina' => 69, 'defense' => 29, 'attack' => 27,
                    'shot' => 24, 'pass' => 25, 'dribble' => 24,
                    'block' => 22, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain équilibré, Hori apporte de la continuité et soutient le rythme collectif.'
            ],

            [
                'Kazushige', 'Enomoto', 12, 'Midfielder', 225,
                [
                    'speed' => 62, 'stamina' => 64, 'defense' => 24, 'attack' => 26,
                    'shot' => 23, 'pass' => 24, 'dribble' => 23,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu offensif de soutien, Enomoto se rend disponible entre les lignes et cherche à accélérer le jeu.'
            ],

            [
                'Noboru', 'Sawaki', 12, 'Forward', 300,
                [
                    'speed' => 66, 'stamina' => 70, 'defense' => 20, 'attack' => 32,
                    'shot' => 27, 'pass' => 22, 'dribble' => 24,
                    'block' => 18, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant puissant, Sawaki cherche la profondeur et profite du jeu collectif pour se créer des occasions.'
            ],

            [
                'Yuichi', 'Suenaga', 12, 'Forward', 320,
                [
                    'speed' => 65, 'stamina' => 69, 'defense' => 20, 'attack' => 34,
                    'shot' => 28, 'pass' => 22, 'dribble' => 25,
                    'block' => 18, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Avant-centre principal de Meiwa, Suenaga est un finisseur efficace qui sait exploiter le travail collectif de son équipe.'
            ],

            // Nakahara
            [
                'Goro', 'Kawakami', 12, 'Goalkeeper', 250,
                [
                    'speed' => 40, 'stamina' => 60, 'defense' => 26, 'attack' => 16,
                    'shot' => 15, 'pass' => 17, 'dribble' => 15,
                    'block' => 20, 'intercept' => 20, 'tackle' => 17,
                    'hand_save' => 23, 'punch_save' => 21
                ],
                'Gardien de Nakahara, Kawakami est un portier simple et appliqué. Il s’appuie sur son placement et tente de limiter les dégâts face à des adversaires plus forts.'
            ],

            [
                'Yuichi', 'Masumoto', 12, 'Midfielder', 240,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 22, 'attack' => 22,
                    'shot' => 21, 'pass' => 22, 'dribble' => 21,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu polyvalent, Masumoto participe à la construction du jeu et soutient les attaques lorsqu’une opportunité se présente.'
            ],

            [
                'Keisuke', 'Haranashi', 12, 'Defender', 230,
                [
                    'speed' => 44, 'stamina' => 57, 'defense' => 25, 'attack' => 18,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 21, 'intercept' => 19, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur discipliné, Haranashi joue bas et ferme les espaces sans chercher la relance compliquée.'
            ],

            [
                'Takamasa', 'Fujita', 12, 'Defender', 235,
                [
                    'speed' => 43, 'stamina' => 58, 'defense' => 25, 'attack' => 18,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 22, 'intercept' => 20, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur sérieux, Fujita renforce la ligne arrière par sa rigueur et son engagement.'
            ],

            [
                'Jin', 'Toda', 12, 'Defender', 225,
                [
                    'speed' => 42, 'stamina' => 56, 'defense' => 24, 'attack' => 17,
                    'shot' => 15, 'pass' => 16, 'dribble' => 15,
                    'block' => 21, 'intercept' => 19, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur de complément, Toda applique les consignes et privilégie la sécurité défensive.'
            ],

            [
                'Ken', 'Nagatani', 12, 'Midfielder', 245,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 22, 'attack' => 22,
                    'shot' => 21, 'pass' => 22, 'dribble' => 21,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain équilibré, Nagatani soutient le jeu collectif et aide à la récupération.'
            ],

            [
                'Shunta', 'Harukawa', 12, 'Midfielder', 240,
                [
                    'speed' => 48, 'stamina' => 59, 'defense' => 21, 'attack' => 21,
                    'shot' => 20, 'pass' => 21, 'dribble' => 20,
                    'block' => 19, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu travailleur, Harukawa apporte du volume de jeu et se rend disponible entre les lignes.'
            ],

            [
                'Susumu', 'Itao', 12, 'Midfielder', 235,
                [
                    'speed' => 46, 'stamina' => 57, 'defense' => 21, 'attack' => 23,
                    'shot' => 22, 'pass' => 22, 'dribble' => 21,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu offensif discret, Itao tente d’apporter de la créativité dans un collectif avant tout prudent.'
            ],

            [
                'Goro', 'Kurita', 12, 'Midfielder', 230,
                [
                    'speed' => 45, 'stamina' => 56, 'defense' => 21, 'attack' => 21,
                    'shot' => 20, 'pass' => 21, 'dribble' => 20,
                    'block' => 19, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Kurita renforce la densité au centre et aide à conserver le ballon.'
            ],

            [
                'Takeshi', 'Asada', 12, 'Forward', 250,
                [
                    'speed' => 49, 'stamina' => 60, 'defense' => 20, 'attack' => 26,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant volontaire, Asada cherche à peser sur la défense adverse par son activité et son sens du placement.'
            ],

            [
                'Shingo', 'Aoi', 12, 'Forward', 450,
                [
                    'speed' => 75, 'stamina' => 72, 'defense' => 25, 'attack' => 36,
                    'shot' => 29, 'pass' => 25, 'dribble' => 28,
                    'block' => 18, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Prodige solitaire de Nakahara, Aoi est un attaquant technique et imprévisible. Doté d’un talent exceptionnel, il peut à lui seul faire basculer un match malgré la faiblesse de son équipe.'
            ],

            // Naniwa
            [
                'Taichi', 'Nakanishi', 12, 'Goalkeeper', 400,
                [
                    'speed' => 40, 'stamina' => 70, 'defense' => 38, 'attack' => 16,
                    'shot' => 15, 'pass' => 18, 'dribble' => 15,
                    'block' => 26, 'intercept' => 24, 'tackle' => 18,
                    'hand_save' => 29, 'punch_save' => 29
                ],
                'Gardien géant de Naniwa, Nakanishi impressionne par son gabarit et sa présence dans les cages. Lent mais extrêmement solide, il ferme l’axe et dégoûte les attaquants par sa résistance.'
            ],

            [
                'Hiroshi', 'Tsusaki', 12, 'Defender', 240,
                [
                    'speed' => 45, 'stamina' => 55, 'defense' => 24, 'attack' => 20,
                    'shot' => 17, 'pass' => 18, 'dribble' => 16,
                    'block' => 21, 'intercept' => 19, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur appliqué, Tsusaki joue bas et protège sa surface en priorité. Il privilégie la sécurité à la relance.'
            ],

            [
                'Kazuya', 'Kosaka', 12, 'Defender', 235,
                [
                    'speed' => 43, 'stamina' => 58, 'defense' => 25, 'attack' => 19,
                    'shot' => 17, 'pass' => 18, 'dribble' => 16,
                    'block' => 22, 'intercept' => 20, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur discipliné, Kosaka ferme les espaces et soutient le marquage collectif de Naniwa.'
            ],

            [
                'Shinji', 'Yoshimoto', 12, 'Defender', 230,
                [
                    'speed' => 44, 'stamina' => 57, 'defense' => 24, 'attack' => 18,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 21, 'intercept' => 20, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur de complément, Yoshimoto renforce la ligne arrière par son sérieux et son sens du placement.'
            ],

            [
                'Daisuke', 'Tennoji', 12, 'Defender', 225,
                [
                    'speed' => 42, 'stamina' => 56, 'defense' => 24, 'attack' => 17,
                    'shot' => 15, 'pass' => 16, 'dribble' => 15,
                    'block' => 21, 'intercept' => 19, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur sobre, Tennoji joue simple et contribue au bloc très compact devant Nakanishi.'
            ],

            [
                'Masato', 'Dojima', 12, 'Midfielder', 245,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 22, 'attack' => 22,
                    'shot' => 21, 'pass' => 22, 'dribble' => 21,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain équilibré, Dojima assure la transition et tente de poser le jeu malgré un rythme lent.'
            ],

            [
                'Ryo', 'Maeda', 12, 'Midfielder', 240,
                [
                    'speed' => 48, 'stamina' => 59, 'defense' => 21, 'attack' => 21,
                    'shot' => 20, 'pass' => 21, 'dribble' => 20,
                    'block' => 19, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu travailleur, Maeda soutient la récupération et sécurise la circulation du ballon.'
            ],

            [
                'Kenji', 'Shirai', 12, 'Midfielder', 235,
                [
                    'speed' => 46, 'stamina' => 57, 'defense' => 21, 'attack' => 23,
                    'shot' => 22, 'pass' => 22, 'dribble' => 21,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu offensif modeste, Shirai tente d’apporter un peu de créativité dans un collectif très défensif.'
            ],

            [
                'Yuta', 'Ogami', 12, 'Midfielder', 230,
                [
                    'speed' => 45, 'stamina' => 56, 'defense' => 21, 'attack' => 21,
                    'shot' => 20, 'pass' => 21, 'dribble' => 20,
                    'block' => 19, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Ogami renforce la densité et aide à conserver le ballon sous pression.'
            ],

            [
                'Satoshi', 'Takayanagi', 12, 'Forward', 250,
                [
                    'speed' => 49, 'stamina' => 60, 'defense' => 20, 'attack' => 26,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant volontaire, Takayanagi tente de peser sur la défense adverse malgré un soutien limité.'
            ],

            [
                'Tetsuya', 'Marui', 12, 'Forward', 240,
                [
                    'speed' => 48, 'stamina' => 58, 'defense' => 19, 'attack' => 24,
                    'shot' => 23, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de complément, Marui profite des rares opportunités pour tenter sa chance.'
            ],

            // Minawi
            [
                'Hajime', 'Asakura', 12, 'Goalkeeper', 225,
                [
                    'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16,
                    'shot' => 15, 'pass' => 18, 'dribble' => 15,
                    'block' => 21, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 25, 'punch_save' => 22
                ],
                'Gardien de Minawi, Asakura est un portier sérieux et appliqué. Il mise sur son placement et la coordination avec sa défense.'
            ],

            [
                'Daichi', 'Azuma', 12, 'Defender', 230,
                [
                    'speed' => 60, 'stamina' => 68, 'defense' => 28, 'attack' => 20,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur fiable, Azuma joue avec rigueur et privilégie l’anticipation plutôt que l’impact physique.'
            ],

            [
                'Shinji', 'Takahama', 12, 'Defender', 235,
                [
                    'speed' => 58, 'stamina' => 66, 'defense' => 29, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur solide, Takahama sécurise l’axe et participe à maintenir un bloc défensif compact.'
            ],

            [
                'Ryu', 'Kawanoe', 12, 'Defender', 240,
                [
                    'speed' => 57, 'stamina' => 67, 'defense' => 29, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 25, 'intercept' => 24, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur constant, Kawanoe apporte de la stabilité et ferme les espaces sur son côté.'
            ],

            [
                'Takashi', 'Iyo', 12, 'Defender', 245,
                [
                    'speed' => 56, 'stamina' => 69, 'defense' => 28, 'attack' => 22,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur combatif, Iyo complète la ligne arrière par son endurance et sa régularité.'
            ],

            [
                'Koji', 'Tosa', 12, 'Midfielder', 210,
                [
                    'speed' => 60, 'stamina' => 62, 'defense' => 24, 'attack' => 26,
                    'shot' => 23, 'pass' => 24, 'dribble' => 23,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu offensif mobile, Tosa apporte de la vivacité et cherche à accélérer le jeu entre les lignes.'
            ],

            [
                'Hiroto', 'Shintani', 12, 'Midfielder', 215,
                [
                    'speed' => 61, 'stamina' => 63, 'defense' => 24, 'attack' => 27,
                    'shot' => 24, 'pass' => 25, 'dribble' => 24,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu créatif, Shintani participe activement à la construction et au jeu rapide de Minawi.'
            ],

            [
                'Tetsuo', 'Ishida', 12, 'Midfielder', 325,
                [
                    'speed' => 69, 'stamina' => 74, 'defense' => 25, 'attack' => 34,
                    'shot' => 28, 'pass' => 24, 'dribble' => 26,
                    'block' => 20, 'intercept' => 22, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Joueur clé de Minawi, Ishida est un milieu offensif explosif et technique. Il dicte le rythme et constitue la principale menace de l’équipe.'
            ],

            [
                'Masaru', 'Hirayama', 12, 'Midfielder', 220,
                [
                    'speed' => 62, 'stamina' => 64, 'defense' => 24, 'attack' => 26,
                    'shot' => 23, 'pass' => 24, 'dribble' => 23,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu polyvalent, Hirayama soutient les phases offensives et maintient l’équilibre du bloc.'
            ],

            [
                'Kazuki', 'Seto', 12, 'Forward', 235,
                [
                    'speed' => 63, 'stamina' => 65, 'defense' => 20, 'attack' => 30,
                    'shot' => 26, 'pass' => 22, 'dribble' => 24,
                    'block' => 18, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant rapide, Seto cherche la profondeur et profite du jeu dynamique du milieu.'
            ],

            [
                'Kazuto', 'Takei', 12, 'Forward', 250,
                [
                    'speed' => 64, 'stamina' => 66, 'defense' => 20, 'attack' => 31,
                    'shot' => 27, 'pass' => 22, 'dribble' => 24,
                    'block' => 18, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Avant-centre de Minawi, Takei est un finisseur opportuniste qui sait exploiter les espaces créés par Ishida.'
            ],

            // Shimizu
            [
                'Morimichi', 'Kawakami', 12, 'Goalkeeper', 300,
                [
                    'speed' => 40, 'stamina' => 65, 'defense' => 26, 'attack' => 16,
                    'shot' => 15, 'pass' => 17, 'dribble' => 15,
                    'block' => 21, 'intercept' => 20, 'tackle' => 17,
                    'hand_save' => 24, 'punch_save' => 22
                ],
                'Gardien de Shimizu, Kawakami est un portier calme et discipliné. Peu mobile, il compense par son placement et sa lecture du jeu.'
            ],

            [
                'Takeshi', 'Kudo', 12, 'Defender', 240,
                [
                    'speed' => 45, 'stamina' => 55, 'defense' => 24, 'attack' => 20,
                    'shot' => 17, 'pass' => 18, 'dribble' => 16,
                    'block' => 21, 'intercept' => 19, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur appliqué, Kudo joue simple et privilégie la protection de sa surface.'
            ],

            [
                'Ichiro', 'Kanda', 12, 'Defender', 235,
                [
                    'speed' => 43, 'stamina' => 58, 'defense' => 25, 'attack' => 19,
                    'shot' => 17, 'pass' => 18, 'dribble' => 16,
                    'block' => 22, 'intercept' => 20, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur rigoureux, Kanda renforce l’axe et ferme les espaces sans prise de risque.'
            ],

            [
                'Yuto', 'Ibaraki', 12, 'Defender', 230,
                [
                    'speed' => 44, 'stamina' => 57, 'defense' => 24, 'attack' => 18,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 21, 'intercept' => 20, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur latéral discret, Ibaraki sécurise son couloir et soutient le bloc défensif.'
            ],

            [
                'Hiroshi', 'Suzuki', 12, 'Defender', 225,
                [
                    'speed' => 42, 'stamina' => 56, 'defense' => 24, 'attack' => 17,
                    'shot' => 15, 'pass' => 16, 'dribble' => 15,
                    'block' => 21, 'intercept' => 19, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur de complément, Suzuki joue bas et applique strictement les consignes défensives.'
            ],

            [
                'Daisuke', 'Takada', 12, 'Midfielder', 245,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 22, 'attack' => 22,
                    'shot' => 21, 'pass' => 22, 'dribble' => 21,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu central, Takada assure la transition et tente de poser le jeu dans un rythme mesuré.'
            ],

            [
                'Ryota', 'Nakao', 12, 'Midfielder', 240,
                [
                    'speed' => 48, 'stamina' => 59, 'defense' => 21, 'attack' => 21,
                    'shot' => 20, 'pass' => 21, 'dribble' => 20,
                    'block' => 19, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu travailleur, Nakao participe au pressing léger et soutient la conservation du ballon.'
            ],

            [
                'Shinji', 'Iimura', 12, 'Midfielder', 235,
                [
                    'speed' => 46, 'stamina' => 57, 'defense' => 21, 'attack' => 23,
                    'shot' => 22, 'pass' => 22, 'dribble' => 21,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu offensif discret, Iimura tente d’apporter un minimum de créativité dans un jeu très prudent.'
            ],

            [
                'Koji', 'Murakami', 12, 'Midfielder', 230,
                [
                    'speed' => 45, 'stamina' => 56, 'defense' => 21, 'attack' => 21,
                    'shot' => 20, 'pass' => 21, 'dribble' => 20,
                    'block' => 19, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Murakami renforce la densité au centre et limite les pertes de balle.'
            ],

            [
                'Kazumasa', 'Kato', 12, 'Forward', 250,
                [
                    'speed' => 49, 'stamina' => 60, 'defense' => 20, 'attack' => 26,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant principal de Shimizu, Kato cherche à exploiter les rares occasions créées par son équipe.'
            ],

            [
                'Takashi', 'Obayashi', 12, 'Forward', 240,
                [
                    'speed' => 48, 'stamina' => 58, 'defense' => 19, 'attack' => 24,
                    'shot' => 23, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de soutien, Obayashi accompagne Kato et tente de peser sur la défense adverse par son activité.'
            ],

            // Shimada
            [
                'Etsuo', 'Nagai', 12, 'Goalkeeper', 250,
                [
                    'speed' => 40, 'stamina' => 60, 'defense' => 26, 'attack' => 16,
                    'shot' => 15, 'pass' => 17, 'dribble' => 15,
                    'block' => 21, 'intercept' => 20, 'tackle' => 17,
                    'hand_save' => 24, 'punch_save' => 22
                ],
                'Gardien de Shimada, Nagai est un portier calme et discipliné. Peu spectaculaire, il mise sur son placement et la solidarité défensive.'
            ],

            [
                'Ikushi', 'Ito', 12, 'Defender', 240,
                [
                    'speed' => 45, 'stamina' => 55, 'defense' => 24, 'attack' => 20,
                    'shot' => 17, 'pass' => 18, 'dribble' => 16,
                    'block' => 21, 'intercept' => 19, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur sérieux, Ito applique un marquage simple et sécurise son couloir sans prise de risque.'
            ],

            [
                'Koichi', 'Fujisawa', 12, 'Defender', 235,
                [
                    'speed' => 43, 'stamina' => 58, 'defense' => 25, 'attack' => 19,
                    'shot' => 17, 'pass' => 18, 'dribble' => 16,
                    'block' => 22, 'intercept' => 20, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur central appliqué, Fujisawa ferme les espaces et privilégie la relance courte.'
            ],

            [
                'Nemto', 'Takahashi', 12, 'Defender', 230,
                [
                    'speed' => 44, 'stamina' => 57, 'defense' => 24, 'attack' => 18,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 21, 'intercept' => 20, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur discret, Takahashi renforce le bloc et se concentre sur la protection de la surface.'
            ],

            [
                'Jo', 'Kimura', 12, 'Midfielder', 245,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 22, 'attack' => 22,
                    'shot' => 21, 'pass' => 22, 'dribble' => 21,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu central, Kimura assure la transition entre défense et attaque avec un jeu simple et propre.'
            ],

            [
                'Koji', 'Ishikawa', 12, 'Defender', 240,
                [
                    'speed' => 48, 'stamina' => 59, 'defense' => 21, 'attack' => 15,
                    'shot' => 14, 'pass' => 21, 'dribble' => 20,
                    'block' => 21, 'intercept' => 22, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur travailleur, Ishikawa soutient la récupération et participe à la conservation du ballon.'
            ],

            [
                'Light', 'Nakamura', 12, 'Midfielder', 240,
                [
                    'speed' => 47, 'stamina' => 58, 'defense' => 21, 'attack' => 21,
                    'shot' => 20, 'pass' => 21, 'dribble' => 20,
                    'block' => 19, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Nakamura renforce la densité au centre et joue sans prise de risque.'
            ],

            [
                'Junichi', 'Nagasaki', 12, 'Midfielder', 250,
                [
                    'speed' => 59, 'stamina' => 60, 'defense' => 23, 'attack' => 26,
                    'shot' => 24, 'pass' => 26, 'dribble' => 26,
                    'block' => 20, 'intercept' => 25, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Avant-centre de Shimada, Nagasaki est un finisseur correct qui tente de convertir les rares occasions.'
            ],

            [
                'Masayuki', 'Jinbo', 12, 'Midfielder', 245,
                [
                    'speed' => 50, 'stamina' => 59, 'defense' => 22, 'attack' => 22,
                    'shot' => 21, 'pass' => 22, 'dribble' => 21,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu polyvalent, Jinbo apporte de l’équilibre et soutient le collectif dans les deux phases.'
            ],

            [
                'Naoki', 'Wesugi', 12, 'Midfielder', 250,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 20, 'attack' => 25,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de complément, Wesugi accompagne Nagasaki et tente d’exister par son activité.'
            ],

            [
                'Takushi', 'Hashimoto', 12, 'Forward', 235,
                [
                    'speed' => 46, 'stamina' => 57, 'defense' => 20, 'attack' => 24,
                    'shot' => 23, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant mobile, Hashimoto cherche à provoquer et à créer des espaces par ses déplacements.'
            ],

            // =======================
            // REMPLAÇANTS ÉQUIPES
            // =======================

            // Nankatsu (1 remplaçant)
            ['Kenji', 'Tomo', 12, 'Midfielder', 180, [
                'speed' => 45, 'stamina' => 65, 'defense' => 20, 'attack' => 22,
                'shot' => 20, 'pass' => 21, 'dribble' => 20, 'block' => 17,
                'intercept' => 18, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Remplaçant polyvalent de Nankatsu.'],

            // Shutetsu (3 remplaçants)
            ['Hiroki', 'Fujii', 12, 'Defender', 160, [
                'speed' => 42, 'stamina' => 60, 'defense' => 24, 'attack' => 17,
                'shot' => 16, 'pass' => 16, 'dribble' => 15, 'block' => 21,
                'intercept' => 19, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Shutetsu.'],
            ['Daisuke', 'Mori', 12, 'Midfielder', 165, [
                'speed' => 44, 'stamina' => 62, 'defense' => 20, 'attack' => 20,
                'shot' => 19, 'pass' => 21, 'dribble' => 19, 'block' => 17,
                'intercept' => 18, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Shutetsu.'],
            ['Yosuke', 'Kimura', 12, 'Forward', 170, [
                'speed' => 46, 'stamina' => 60, 'defense' => 16, 'attack' => 23,
                'shot' => 22, 'pass' => 18, 'dribble' => 20, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Shutetsu.'],

            // Toho (3 remplaçants)
            ['Ryuji', 'Endo', 12, 'Defender', 165, [
                'speed' => 55, 'stamina' => 65, 'defense' => 26, 'attack' => 19,
                'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 22,
                'intercept' => 20, 'tackle' => 23, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Toho.'],
            ['Masashi', 'Goto', 12, 'Midfielder', 170, [
                'speed' => 56, 'stamina' => 63, 'defense' => 22, 'attack' => 21,
                'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 18,
                'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Toho.'],
            ['Keita', 'Ogawa', 12, 'Forward', 175, [
                'speed' => 60, 'stamina' => 62, 'defense' => 17, 'attack' => 25,
                'shot' => 23, 'pass' => 19, 'dribble' => 21, 'block' => 15,
                'intercept' => 15, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Toho.'],

            // Furano (3 remplaçants)
            ['Sosuke', 'Maeda', 12, 'Defender', 160, [
                'speed' => 52, 'stamina' => 68, 'defense' => 25, 'attack' => 18,
                'shot' => 16, 'pass' => 17, 'dribble' => 15, 'block' => 21,
                'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Furano.'],
            ['Tetsuya', 'Iida', 12, 'Midfielder', 165, [
                'speed' => 55, 'stamina' => 66, 'defense' => 22, 'attack' => 21,
                'shot' => 19, 'pass' => 22, 'dribble' => 20, 'block' => 18,
                'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Furano.'],
            ['Noboru', 'Hayashi', 12, 'Forward', 170, [
                'speed' => 58, 'stamina' => 64, 'defense' => 17, 'attack' => 24,
                'shot' => 22, 'pass' => 19, 'dribble' => 21, 'block' => 15,
                'intercept' => 15, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Furano.'],

            // Musashi (3 remplaçants)
            ['Hiroyuki', 'Noda', 12, 'Defender', 160, [
                'speed' => 42, 'stamina' => 62, 'defense' => 25, 'attack' => 18,
                'shot' => 16, 'pass' => 17, 'dribble' => 15, 'block' => 21,
                'intercept' => 19, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Musashi.'],
            ['Junpei', 'Aoki', 12, 'Midfielder', 165, [
                'speed' => 44, 'stamina' => 63, 'defense' => 21, 'attack' => 20,
                'shot' => 19, 'pass' => 21, 'dribble' => 19, 'block' => 17,
                'intercept' => 18, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Musashi.'],
            ['Satoru', 'Koyama', 12, 'Forward', 170, [
                'speed' => 57, 'stamina' => 61, 'defense' => 16, 'attack' => 23,
                'shot' => 22, 'pass' => 18, 'dribble' => 20, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Musashi.'],

            // Hanawa (3 remplaçants)
            ['Takuro', 'Fujimoto', 12, 'Defender', 155, [
                'speed' => 50, 'stamina' => 60, 'defense' => 24, 'attack' => 17,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 21,
                'intercept' => 19, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Hanawa.'],
            ['Yuki', 'Ozawa', 12, 'Midfielder', 160, [
                'speed' => 52, 'stamina' => 61, 'defense' => 21, 'attack' => 20,
                'shot' => 19, 'pass' => 21, 'dribble' => 19, 'block' => 17,
                'intercept' => 18, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Hanawa.'],
            ['Osamu', 'Miyata', 12, 'Forward', 165, [
                'speed' => 55, 'stamina' => 60, 'defense' => 16, 'attack' => 22,
                'shot' => 21, 'pass' => 18, 'dribble' => 19, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Hanawa.'],

            // Azumaichi (3 remplaçants)
            ['Kengo', 'Ueda', 12, 'Defender', 160, [
                'speed' => 53, 'stamina' => 63, 'defense' => 25, 'attack' => 18,
                'shot' => 16, 'pass' => 17, 'dribble' => 15, 'block' => 21,
                'intercept' => 19, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant d\'Azumaichi.'],
            ['Shohei', 'Kawai', 12, 'Midfielder', 165, [
                'speed' => 50, 'stamina' => 62, 'defense' => 21, 'attack' => 20,
                'shot' => 19, 'pass' => 21, 'dribble' => 19, 'block' => 18,
                'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant d\'Azumaichi.'],
            ['Tomoya', 'Ishida', 12, 'Forward', 170, [
                'speed' => 56, 'stamina' => 61, 'defense' => 16, 'attack' => 23,
                'shot' => 22, 'pass' => 18, 'dribble' => 20, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant d\'Azumaichi.'],

            // Hirado (3 remplaçants)
            ['Makoto', 'Fukuda', 12, 'Defender', 160, [
                'speed' => 52, 'stamina' => 64, 'defense' => 25, 'attack' => 18,
                'shot' => 16, 'pass' => 17, 'dribble' => 15, 'block' => 22,
                'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Hirado.'],
            ['Ryo', 'Kawamoto', 12, 'Midfielder', 165, [
                'speed' => 49, 'stamina' => 63, 'defense' => 21, 'attack' => 20,
                'shot' => 19, 'pass' => 21, 'dribble' => 19, 'block' => 17,
                'intercept' => 18, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Hirado.'],
            ['Tatsuya', 'Kimura', 12, 'Forward', 170, [
                'speed' => 55, 'stamina' => 62, 'defense' => 16, 'attack' => 23,
                'shot' => 22, 'pass' => 18, 'dribble' => 20, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Hirado.'],

            // Otomo (3 remplaçants)
            ['Shunsuke', 'Wada', 12, 'Defender', 165, [
                'speed' => 46, 'stamina' => 63, 'defense' => 26, 'attack' => 18,
                'shot' => 16, 'pass' => 17, 'dribble' => 15, 'block' => 22,
                'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant d\'Otomo.'],
            ['Yuya', 'Saeki', 12, 'Midfielder', 170, [
                'speed' => 48, 'stamina' => 64, 'defense' => 22, 'attack' => 21,
                'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 18,
                'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant d\'Otomo.'],
            ['Kazuki', 'Hara', 12, 'Forward', 175, [
                'speed' => 54, 'stamina' => 62, 'defense' => 17, 'attack' => 24,
                'shot' => 23, 'pass' => 19, 'dribble' => 21, 'block' => 15,
                'intercept' => 15, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant d\'Otomo.'],

            // Meiwa (3 remplaçants)
            ['Tomohiro', 'Abe', 12, 'Defender', 160, [
                'speed' => 53, 'stamina' => 62, 'defense' => 25, 'attack' => 18,
                'shot' => 16, 'pass' => 17, 'dribble' => 15, 'block' => 22,
                'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Meiwa.'],
            ['Nobuhiro', 'Suzuki', 12, 'Midfielder', 165, [
                'speed' => 55, 'stamina' => 63, 'defense' => 22, 'attack' => 21,
                'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 18,
                'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Meiwa.'],
            ['Fumihiro', 'Kato', 12, 'Forward', 170, [
                'speed' => 57, 'stamina' => 62, 'defense' => 17, 'attack' => 24,
                'shot' => 23, 'pass' => 19, 'dribble' => 21, 'block' => 15,
                'intercept' => 15, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Meiwa.'],

            // Nakahara (3 remplaçants)
            ['Ikki', 'Miura', 12, 'Defender', 155, [
                'speed' => 40, 'stamina' => 58, 'defense' => 23, 'attack' => 17,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 20,
                'intercept' => 18, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Nakahara.'],
            ['Toshiki', 'Ono', 12, 'Midfielder', 160, [
                'speed' => 42, 'stamina' => 59, 'defense' => 20, 'attack' => 20,
                'shot' => 19, 'pass' => 20, 'dribble' => 19, 'block' => 17,
                'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Nakahara.'],
            ['Hiroki', 'Tanaka', 12, 'Forward', 165, [
                'speed' => 46, 'stamina' => 58, 'defense' => 15, 'attack' => 22,
                'shot' => 21, 'pass' => 17, 'dribble' => 19, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Nakahara.'],

            // Naniwa (3 remplaçants)
            ['Atsushi', 'Goto', 12, 'Defender', 155, [
                'speed' => 40, 'stamina' => 57, 'defense' => 23, 'attack' => 17,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 20,
                'intercept' => 18, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Naniwa.'],
            ['Kohei', 'Imai', 12, 'Midfielder', 160, [
                'speed' => 41, 'stamina' => 58, 'defense' => 20, 'attack' => 19,
                'shot' => 18, 'pass' => 20, 'dribble' => 18, 'block' => 17,
                'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Naniwa.'],
            ['Daizo', 'Shimizu', 12, 'Forward', 165, [
                'speed' => 44, 'stamina' => 57, 'defense' => 15, 'attack' => 21,
                'shot' => 20, 'pass' => 17, 'dribble' => 18, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Naniwa.'],

            // Minawi (3 remplaçants)
            ['Ryo', 'Fujita', 12, 'Defender', 155, [
                'speed' => 51, 'stamina' => 60, 'defense' => 24, 'attack' => 17,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 20,
                'intercept' => 18, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Minawi.'],
            ['Kenta', 'Wada', 12, 'Midfielder', 160, [
                'speed' => 52, 'stamina' => 60, 'defense' => 20, 'attack' => 20,
                'shot' => 19, 'pass' => 21, 'dribble' => 19, 'block' => 17,
                'intercept' => 18, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Minawi.'],
            ['Yuji', 'Morita', 12, 'Forward', 165, [
                'speed' => 55, 'stamina' => 59, 'defense' => 15, 'attack' => 22,
                'shot' => 21, 'pass' => 17, 'dribble' => 19, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Minawi.'],

            // Shimizu (3 remplaçants)
            ['Naoto', 'Kishi', 12, 'Defender', 155, [
                'speed' => 40, 'stamina' => 56, 'defense' => 23, 'attack' => 17,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 20,
                'intercept' => 18, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Shimizu.'],
            ['Yosuke', 'Murata', 12, 'Midfielder', 160, [
                'speed' => 42, 'stamina' => 57, 'defense' => 20, 'attack' => 19,
                'shot' => 18, 'pass' => 20, 'dribble' => 18, 'block' => 17,
                'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Shimizu.'],
            ['Kazuya', 'Nishida', 12, 'Forward', 165, [
                'speed' => 46, 'stamina' => 58, 'defense' => 15, 'attack' => 22,
                'shot' => 21, 'pass' => 17, 'dribble' => 19, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Shimizu.'],

            // Shimada (3 remplaçants)
            ['Hiroshi', 'Okamoto', 12, 'Defender', 155, [
                'speed' => 41, 'stamina' => 57, 'defense' => 23, 'attack' => 17,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 20,
                'intercept' => 18, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Shimada.'],
            ['Kenji', 'Tsuda', 12, 'Midfielder', 160, [
                'speed' => 43, 'stamina' => 58, 'defense' => 20, 'attack' => 20,
                'shot' => 19, 'pass' => 20, 'dribble' => 19, 'block' => 17,
                'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Shimada.'],
            ['Takuya', 'Nozaki', 12, 'Forward', 165, [
                'speed' => 47, 'stamina' => 58, 'defense' => 15, 'attack' => 22,
                'shot' => 21, 'pass' => 17, 'dribble' => 19, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Shimada.'],

            // =======================
            // JOUEURS LIBRE
            // =======================

            // Real Seven – Not contract
            [
                'Michel', 'Yamada', 13, 'Goalkeeper', 300,
                [
                    'speed' => 60, 'stamina' => 70, 'defense' => 32, 'attack' => 18,
                    'shot' => 16, 'pass' => 19, 'dribble' => 17,
                    'block' => 22, 'intercept' => 23, 'tackle' => 18,
                    'hand_save' => 26, 'punch_save' => 24
                ],
                'Gardien de Real Seven, Yamada est un portier athlétique et fiable. Plus mobile que la moyenne, il combine réflexes et lecture du jeu.'
            ],

            [
                'Yuji', 'Sakaki', 13, 'Defender', 285,
                [
                    'speed' => 58, 'stamina' => 67, 'defense' => 30, 'attack' => 22,
                    'shot' => 18, 'pass' => 20, 'dribble' => 18,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur solide, Sakaki se distingue par son sens du placement et sa régularité dans les duels.'
            ],

            [
                'Yuji', 'Soga', 13, 'Defender', 290,
                [
                    'speed' => 60, 'stamina' => 68, 'defense' => 31, 'attack' => 23,
                    'shot' => 19, 'pass' => 20, 'dribble' => 18,
                    'block' => 25, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur combatif, Soga apporte de l’impact physique et sécurise l’axe défensif.'
            ],

            [
                'Gakuto', 'Igawa', 13, 'Midfielder', 295,
                [
                    'speed' => 62, 'stamina' => 68, 'defense' => 28, 'attack' => 25,
                    'shot' => 23, 'pass' => 24, 'dribble' => 23,
                    'block' => 21, 'intercept' => 22, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu polyvalent, Igawa participe aussi bien à la récupération qu’à la projection offensive.'
            ],

            [
                'Kotaru', 'Furukawa', 13, 'Midfielder', 300,
                [
                    'speed' => 63, 'stamina' => 69, 'defense' => 29, 'attack' => 26,
                    'shot' => 24, 'pass' => 25, 'dribble' => 24,
                    'block' => 21, 'intercept' => 22, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu technique, Furukawa se rend disponible entre les lignes et fluidifie le jeu collectif.'
            ],

            [
                'Takashi', 'Sugimoto', 13, 'Midfielder', 305,
                [
                    'speed' => 65, 'stamina' => 70, 'defense' => 30, 'attack' => 27,
                    'shot' => 25, 'pass' => 26, 'dribble' => 25,
                    'block' => 22, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu complet, Sugimoto est un relais essentiel entre la défense et l’attaque de Real Seven.'
            ],

            [
                'Nobuyuki', 'Yumikura', 13, 'Midfielder', 310,
                [
                    'speed' => 66, 'stamina' => 71, 'defense' => 31, 'attack' => 28,
                    'shot' => 26, 'pass' => 27, 'dribble' => 26,
                    'block' => 22, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu leader, Yumikura impose le tempo et apporte une grande constance dans le jeu.'
            ],

            [
                'Toshiya', 'Okano', 13, 'Midfielder', 300,
                [
                    'speed' => 64, 'stamina' => 69, 'defense' => 29, 'attack' => 27,
                    'shot' => 25, 'pass' => 26, 'dribble' => 25,
                    'block' => 22, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu offensif régulier, Okano soutient les attaques et se projette intelligemment.'
            ],

            [
                'Shinnosuke', 'Kazami', 13, 'Forward', 315,
                [
                    'speed' => 67, 'stamina' => 70, 'defense' => 22, 'attack' => 36,
                    'shot' => 28, 'pass' => 22, 'dribble' => 26,
                    'block' => 18, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant rapide et opportuniste, Kazami multiplie les appels et profite du jeu collectif.'
            ],

            [
                'Ryoma', 'Hino', 12, 'Forward', 445,
                [
                    'speed' => 70, 'stamina' => 80, 'defense' => 20, 'attack' => 38,
                    'shot' => 30, 'pass' => 20, 'dribble' => 27,
                    'block' => 18, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Star offensive de Real Seven, Hino est un buteur puissant et agressif. Son tir dévastateur et son mental font de lui une menace constante.'
            ],

            // L'École des Champions – Not contract

// Columbus / Gênes / Ailes de Jupiter
            [
                'Benjamin', 'Lefranc', 13, 'Forward', 420,
                [
                    'speed' => 68, 'stamina' => 74, 'defense' => 20, 'attack' => 36,
                    'shot' => 28, 'pass' => 26, 'dribble' => 27,
                    'block' => 17, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine charismatique des Ailes de Jupiter. Benjamin se distingue par sa détermination, sa vision du jeu et sa capacité à élever son niveau dans les grands moments.'
            ],
            [
                'Eric', 'Townsend', 13, 'Forward', 390,
                [
                    'speed' => 72, 'stamina' => 70, 'defense' => 19, 'attack' => 33,
                    'shot' => 26, 'pass' => 24, 'dribble' => 28,
                    'block' => 16, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine de Columbus devenu lieutenant fidèle de Benjamin. Il se dépasse régulièrement face aux frappes puissantes de Cesare, payant de sa personne pour sauver son équipe.'
            ],
            [
                'Lucas', 'Rondi', 13, 'Defender', 385,
                [
                    'speed' => 70, 'stamina' => 68, 'defense' => 26, 'attack' => 22,
                    'shot' => 17, 'pass' => 20, 'dribble' => 28,
                    'block' => 24, 'intercept' => 22, 'tackle' => 23,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Libéro acrobate, fils d\'une famille de circassiens. Dribbleur hors pair, il se distingue par ses acrobaties spectaculaires et rivalise d\'inventivité avec Papan.'
            ],
            [
                'Cesare', 'Gatti', 13, 'Forward', 440,
                [
                    'speed' => 70, 'stamina' => 76, 'defense' => 22, 'attack' => 38,
                    'shot' => 30, 'pass' => 22, 'dribble' => 26,
                    'block' => 17, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant puissant au sang chaud, rival et partenaire de Benjamin. Son tir dévastateur est l\'un des plus redoutables de sa génération.'
            ],
            [
                'Roberto', 'Concini', 13, 'Midfielder', 350,
                [
                    'speed' => 62, 'stamina' => 68, 'defense' => 23, 'attack' => 28,
                    'shot' => 21, 'pass' => 26, 'dribble' => 23,
                    'block' => 19, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu de Columbus devenu un joueur fiable au contact de Benjamin. Sympathique et impliqué, il soutient activement le jeu collectif.'
            ],
            [
                'Macaroni', 'Giotti', 13, 'Defender', 345,
                [
                    'speed' => 58, 'stamina' => 70, 'defense' => 28, 'attack' => 18,
                    'shot' => 14, 'pass' => 17, 'dribble' => 16,
                    'block' => 26, 'intercept' => 22, 'tackle' => 26,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Défenseur de Columbus qui a progressé grâce à Benjamin. Ses efforts ont été récompensés par une sélection à Gênes. Il parvient même à repousser les tirs de l\'aigle en finale.'
            ],
            [
                'Giorgio', 'Fornari', 13, 'Goalkeeper', 280,
                [
                    'speed' => 50, 'stamina' => 62, 'defense' => 22, 'attack' => 12,
                    'shot' => 10, 'pass' => 14, 'dribble' => 12,
                    'block' => 20, 'intercept' => 18, 'tackle' => 16,
                    'hand_save' => 24, 'punch_save' => 22,
                ],
                'Gardien de Columbus, peu sûr de lui mais qui progresse grâce aux conseils de Benjamin. Il apprend à surmonter sa peur face aux tirs puissants de Cesare.'
            ],
            [
                'Antonio', 'Solozzo', 13, 'Midfielder', 270,
                [
                    'speed' => 54, 'stamina' => 62, 'defense' => 20, 'attack' => 22,
                    'shot' => 18, 'pass' => 21, 'dribble' => 19,
                    'block' => 17, 'intercept' => 18, 'tackle' => 17,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu discret de Columbus qui manque de confiance en lui. L\'arrivée de Benjamin l\'encourage à se surpasser lors des entraînements.'
            ],

// San Podesta Jr
            [
                'Mario', 'Santis', 13, 'Midfielder', 395,
                [
                    'speed' => 64, 'stamina' => 72, 'defense' => 26, 'attack' => 30,
                    'shot' => 22, 'pass' => 28, 'dribble' => 24,
                    'block' => 20, 'intercept' => 24, 'tackle' => 22,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Meneur de jeu de San Podesta Jr, lieutenant de Julian. Son intelligence tactique hors norme lui permet d\'anticiper les plans adverses et de lancer ses partenaires dans les meilleures conditions.'
            ],
            [
                'Renato', 'Salgari', 13, 'Forward', 400,
                [
                    'speed' => 70, 'stamina' => 64, 'defense' => 18, 'attack' => 34,
                    'shot' => 27, 'pass' => 23, 'dribble' => 26,
                    'block' => 16, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant incroyablement doué capable de reproduire les tirs de Julian et Hikaru même en déséquilibre. Manque encore d\'expérience et se fatigue vite dans les grands matchs.'
            ],
            [
                'Renzo', 'Rotta', 13, 'Forward', 370,
                [
                    'speed' => 66, 'stamina' => 68, 'defense' => 18, 'attack' => 31,
                    'shot' => 25, 'pass' => 22, 'dribble' => 23,
                    'block' => 16, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant de San Podesta Jr, remplaçant de Julian. Sélectionné à Gênes, il marque plusieurs buts précieux avant de s\'imposer comme titulaire à son retour.'
            ],
            [
                'Coloni', 'Bottini', 13, 'Goalkeeper', 320,
                [
                    'speed' => 52, 'stamina' => 66, 'defense' => 24, 'attack' => 12,
                    'shot' => 10, 'pass' => 15, 'dribble' => 12,
                    'block' => 22, 'intercept' => 20, 'tackle' => 17,
                    'hand_save' => 27, 'punch_save' => 25,
                ],
                'Gardien de San Podesta Jr, fiable et régulier dans ses interventions. Il soutient solidement son équipe lors des grands matchs.'
            ],
            [
                'Bruno', 'Moricone', 13, 'Midfielder', 360,
                [
                    'speed' => 66, 'stamina' => 68, 'defense' => 24, 'attack' => 30,
                    'shot' => 23, 'pass' => 26, 'dribble' => 25,
                    'block' => 19, 'intercept' => 22, 'tackle' => 21,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Joueur vedette de Margherita, individualiste au départ. Il adopte l\'esprit d\'équipe après le premier match de Gênes et devient un atout collectif précieux.'
            ],
            [
                'Alfredo', 'Pettri', 13, 'Defender', 340,
                [
                    'speed' => 58, 'stamina' => 72, 'defense' => 27, 'attack' => 16,
                    'shot' => 13, 'pass' => 16, 'dribble' => 15,
                    'block' => 26, 'intercept' => 22, 'tackle' => 26,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Défenseur de petite stature mais extrêmement tenace. Il voue un culte à Cesare et est capable de stopper les meilleurs joueurs par sa ténacité acharnée.'
            ],

// Jumeaux Biancchi
            [
                'Nino', 'Biancchi', 13, 'Forward', 355,
                [
                    'speed' => 64, 'stamina' => 68, 'defense' => 18, 'attack' => 30,
                    'shot' => 26, 'pass' => 24, 'dribble' => 22,
                    'block' => 16, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Jumeau attaquant de Corvette. Grand et doté d\'une excellente détente, il excelle dans le jeu de tête et les une-deux foudroyants avec son frère Riki.'
            ],
            [
                'Riki', 'Biancchi', 13, 'Forward', 355,
                [
                    'speed' => 64, 'stamina' => 68, 'defense' => 18, 'attack' => 30,
                    'shot' => 26, 'pass' => 24, 'dribble' => 22,
                    'block' => 16, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Jumeau attaquant de Corvette. En duo avec Nino, leur synchronisation parfaite et leur jeu aérien font d\'eux une menace constante sur les phases arrêtées.'
            ],

// Naples
            [
                'Woltz', 'Hoffmann', 13, 'Goalkeeper', 420,
                [
                    'speed' => 62, 'stamina' => 74, 'defense' => 30, 'attack' => 14,
                    'shot' => 12, 'pass' => 16, 'dribble' => 14,
                    'block' => 26, 'intercept' => 24, 'tackle' => 20,
                    'hand_save' => 24, 'punch_save' => 25,
                ],
                'Gardien allemand grand et massif mais très agile. Il anticipe la direction des tirs en observant les pieds des tireurs, ce qui lui permet des réactions fulgurantes.'
            ],
            [
                'Ricardo', 'Costello', 13, 'Midfielder', 360,
                [
                    'speed' => 64, 'stamina' => 68, 'defense' => 24, 'attack' => 28,
                    'shot' => 21, 'pass' => 24, 'dribble' => 22,
                    'block' => 19, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Lieutenant de Cesare à Naples. Joueur au sang chaud, il est le seul personnage de la série expulsé suite à un carton rouge face à l\'équipe de Trente.'
            ],

// Ailes de Jupiter
            [
                'Papan', 'Correia Da Silva', 13, 'Midfielder', 430,
                [
                    'speed' => 74, 'stamina' => 62, 'defense' => 20, 'attack' => 36,
                    'shot' => 26, 'pass' => 28, 'dribble' => 32,
                    'block' => 17, 'intercept' => 19, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu acrobatique au talent immense mais à la discipline aléatoire. Renvoyé de 14 équipes, il rejoint les Ailes de Jupiter et se lie d\'amitié avec Lucas après leur rivalité acrobatique.'
            ],
            [
                'Ash', 'Rodrigues', 13, 'Defender', 375,
                [
                    'speed' => 68, 'stamina' => 70, 'defense' => 28, 'attack' => 20,
                    'shot' => 16, 'pass' => 19, 'dribble' => 18,
                    'block' => 26, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Libéro brésilien évoluant en Allemagne. Il souffre d\'un blocage psychologique suite à un accident mais surmonte sa peur grâce à Yann, devenant un défenseur solide des Ailes de Jupiter.'
            ],
            [
                'Yann', 'Haarden', 13, 'Forward', 420,
                [
                    'speed' => 70, 'stamina' => 76, 'defense' => 24, 'attack' => 36,
                    'shot' => 28, 'pass' => 22, 'dribble' => 26,
                    'block' => 19, 'intercept' => 20, 'tackle' => 22,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant très physique des Pays-Bas. Stratège redoutable, il utilise Cesare à son insu pour faire le ménage dans les défenses. Il forme un trio de choc avec Cesare et Ash.'
            ],
            [
                'Peter', 'Shilton', 13, 'Defender', 380,
                [
                    'speed' => 62, 'stamina' => 72, 'defense' => 30, 'attack' => 18,
                    'shot' => 14, 'pass' => 18, 'dribble' => 16,
                    'block' => 28, 'intercept' => 25, 'tackle' => 28,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine de l\'équipe nationale d\'Angleterre. Défenseur imposant et autoritaire, il rejoint les Ailes de Jupiter pour la Coupe du monde des clubs.'
            ],
            [
                'Marcel', 'Beauregard', 13, 'Midfielder', 395,
                [
                    'speed' => 65, 'stamina' => 72, 'defense' => 24, 'attack' => 31,
                    'shot' => 22, 'pass' => 30, 'dribble' => 25,
                    'block' => 19, 'intercept' => 23, 'tackle' => 21,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine de l\'équipe de France, réputé pour la précision millimétrée de ses passes. Il rejoint les Ailes de Jupiter après avoir surpassé Eric, qui l\'avait humilié par le passé.'
            ],

// Milan
            [
                'Jean', 'Levaillant', 13, 'Midfielder', 430,
                [
                    'speed' => 68, 'stamina' => 74, 'defense' => 26, 'attack' => 34,
                    'shot' => 24, 'pass' => 30, 'dribble' => 28,
                    'block' => 20, 'intercept' => 24, 'tackle' => 22,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine de Milan, surnommé "Le magicien". L\'un des joueurs les plus techniques de la série, il sait pratiquement tout faire avec un ballon.'
            ],

// Rome
            [
                'Nero', 'Martella', 13, 'Forward', 370,
                [
                    'speed' => 66, 'stamina' => 68, 'defense' => 19, 'attack' => 31,
                    'shot' => 24, 'pass' => 26, 'dribble' => 23,
                    'block' => 16, 'intercept' => 18, 'tackle' => 17,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine de Rome, attaquant jouant collectivement. Avec Erio et Bento, il pratique un jeu de passes très efficace qui déstabilise les défenses adverses.'
            ],
            [
                'Erio', 'Caruso', 13, 'Forward', 355,
                [
                    'speed' => 64, 'stamina' => 66, 'defense' => 18, 'attack' => 29,
                    'shot' => 22, 'pass' => 25, 'dribble' => 22,
                    'block' => 15, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant de Rome faisant partie du trio offensif avec Nero et Bento. Leur jeu collectif et leurs passes rapides sont leur principale force.'
            ],
            [
                'Bento', 'Capone', 13, 'Forward', 355,
                [
                    'speed' => 64, 'stamina' => 66, 'defense' => 18, 'attack' => 29,
                    'shot' => 22, 'pass' => 25, 'dribble' => 22,
                    'block' => 15, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant de Rome, troisième élément du trio offensif avec Nero et Erio. Ensemble ils pratiquent un football collectif basé sur des échanges rapides et précis.'
            ],

// Falcon Jr
            [
                'Carlos', 'Oliveira Tavares', 13, 'Forward', 395,
                [
                    'speed' => 68, 'stamina' => 72, 'defense' => 18, 'attack' => 34,
                    'shot' => 28, 'pass' => 20, 'dribble' => 24,
                    'block' => 16, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine de Falcon Jr. Attaquant brésilien spécialiste des tirs à effet, redouté dans les duels offensifs et les situations de coup de pied arrêté.'
            ],
            [
                'Giovanni', 'Barbossa-Perreira', 13, 'Forward', 360,
                [
                    'speed' => 66, 'stamina' => 68, 'defense' => 17, 'attack' => 30,
                    'shot' => 24, 'pass' => 22, 'dribble' => 22,
                    'block' => 15, 'intercept' => 16, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Lieutenant de Carlos au sein de Falcon Jr. Attaquant combatif et fidèle second de son capitaine.'
            ],

// Pegasus FC
            [
                'Ken', 'Hinamori', 13, 'Midfielder', 400,
                [
                    'speed' => 70, 'stamina' => 72, 'defense' => 28, 'attack' => 32,
                    'shot' => 24, 'pass' => 26, 'dribble' => 26,
                    'block' => 24, 'intercept' => 26, 'tackle' => 26,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine du Dragon Rouge de Pegasus FC et ceinture noire de karaté. Il s\'inspire de cet art martial dans son jeu et admire profondément Hikaru, rêvant de le surpasser.'
            ],
            [
                'Ryo', 'Sakura', 13, 'Goalkeeper', 415,
                [
                    'speed' => 60, 'stamina' => 72, 'defense' => 28, 'attack' => 12,
                    'shot' => 10, 'pass' => 15, 'dribble' => 13,
                    'block' => 24, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 32, 'punch_save' => 30,
                ],
                'Gardien de Pegasus FC s\'inspirant du karaté pour ses parades. Avec Woltz, il est sans doute l\'un des deux meilleurs gardiens de la série.'
            ],

            //-------------------------------
            // Hungry Heart – Not contract
            //-------------------------------
            [
                'Kyosuke', 'Kano', 13, 'Forward', 380,
                [
                    'speed' => 68, 'stamina' => 68, 'defense' => 16, 'attack' => 30,
                    'shot' => 24, 'pass' => 20, 'dribble' => 24,
                    'block' => 14, 'intercept' => 15, 'tackle' => 14,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant fougueux de Jyoyo, surnommé Orangehead. Formé par son frère Seisuke, il retrouve sa passion du football au contact de ses coéquipiers et s\'impose comme l\'attaquant vedette de l\'équipe.'
            ],

//            [
//                'Seisuke', 'Kano', 17, 'Midfielder', 460,
//                [
//                    'speed' => 72, 'stamina' => 76, 'defense' => 24, 'attack' => 38,
//                    'shot' => 28, 'pass' => 32, 'dribble' => 28,
//                    'block' => 18, 'intercept' => 22, 'tackle' => 20,
//                    'hand_save' => 10, 'punch_save' => 10,
//                ],
//                'Frère aîné de Kyosuke, prodige reconnu. Il a guidé ses équipes aux nationaux et vise déjà une carrière professionnelle. Meneur de jeu technique et visionnaire.'
//            ],

            [
                'Rafael', 'Del Franco', 13, 'Midfielder', 355,
                [
                    'speed' => 64, 'stamina' => 66, 'defense' => 20, 'attack' => 28,
                    'shot' => 20, 'pass' => 26, 'dribble' => 23,
                    'block' => 16, 'intercept' => 19, 'tackle' => 17,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Meneur de jeu brésilien de Jyoyo. D\'abord individualiste et motivé par l\'argent pour aider sa famille, il évolue au contact de Kyosuke et devient le cerveau de son équipe.'
            ],

            [
                'Koji', 'Sakai', 13, 'Goalkeeper', 330,
                [
                    'speed' => 52, 'stamina' => 64, 'defense' => 22, 'attack' => 12,
                    'shot' => 10, 'pass' => 14, 'dribble' => 12,
                    'block' => 20, 'intercept' => 18, 'tackle' => 15,
                    'hand_save' => 26, 'punch_save' => 24,
                ],
                'Gardien mi-japonais mi-suédén de Jyoyo. Apprécié des filles, il surmonte sa peur des blessures grâce à ses coéquipiers et s\'affirme progressivement comme un gardien fiable.'
            ],

            [
                'Gohzo', 'Kamata', 13, 'Defender', 305,
                [
                    'speed' => 56, 'stamina' => 66, 'defense' => 25, 'attack' => 16,
                    'shot' => 13, 'pass' => 15, 'dribble' => 14,
                    'block' => 23, 'intercept' => 20, 'tackle' => 23,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Défenseur et vice-capitaine de Jyoyo, ancien avant reconverti. Leader défensif sérieux et respecté de ses coéquipiers.'
            ],

            [
                'Yoshiya', 'Sako', 13, 'Midfielder', 295,
                [
                    'speed' => 58, 'stamina' => 62, 'defense' => 22, 'attack' => 23,
                    'shot' => 17, 'pass' => 23, 'dribble' => 19,
                    'block' => 16, 'intercept' => 19, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine et meneur de jeu de Jyoyo avant l\'arrivée de Rodrigo. Posé et intelligent, il aide toujours ses coéquipiers et reste un joueur fiable.'
            ],

            [
                'Hiroshi', 'Ichikawa', 13, 'Midfielder', 280,
                [
                    'speed' => 60, 'stamina' => 60, 'defense' => 20, 'attack' => 22,
                    'shot' => 17, 'pass' => 20, 'dribble' => 19,
                    'block' => 16, 'intercept' => 18, 'tackle' => 17,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu fougueux de Jyoyo. Il croit profondément au jeu collectif et s\'oppose d\'abord à l\'individualisme de Rodrigo avant de s\'imposer comme vice-capitaine.'
            ],

            [
                'Masashi', 'Esaka', 13, 'Midfielder', 270,
                [
                    'speed' => 56, 'stamina' => 62, 'defense' => 19, 'attack' => 21,
                    'shot' => 16, 'pass' => 20, 'dribble' => 18,
                    'block' => 15, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu jovial de Jyoyo, passionné par le bien-être de l\'équipe. Sako et Kamata le choisissent comme nouveau capitaine pour son attitude exemplaire.'
            ],

            [
                'Yuya', 'Kiba', 13, 'Forward', 320,
                [
                    'speed' => 72, 'stamina' => 62, 'defense' => 14, 'attack' => 26,
                    'shot' => 21, 'pass' => 16, 'dribble' => 22,
                    'block' => 13, 'intercept' => 14, 'tackle' => 13,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant rapide rival de Kyosuke, surnommé Nesthead. Sa vitesse et sa frappe sont ses principaux atouts, mais il manque encore de maturité.'
            ],

            [
                'Masahiko', 'Shinkawa', 13, 'Midfielder', 300,
                [
                    'speed' => 76, 'stamina' => 58, 'defense' => 16, 'attack' => 22,
                    'shot' => 16, 'pass' => 18, 'dribble' => 24,
                    'block' => 14, 'intercept' => 15, 'tackle' => 14,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu ultra-rapide et ami proche de Yuya. Sa vitesse et ses dribbles en font un joueur difficile à stopper malgré son jeune âge.'
            ],

            [
                'Kazuya', 'Muroi', 13, 'Defender', 270,
                [
                    'speed' => 58, 'stamina' => 62, 'defense' => 23, 'attack' => 14,
                    'shot' => 12, 'pass' => 14, 'dribble' => 13,
                    'block' => 22, 'intercept' => 18, 'tackle' => 22,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Défenseur reconnaissable à sa coupe mohawk. Il joue au football pour aider Yuya à réaliser son rêve, apportant solidité et engagement défensif.'
            ],

            //-------------------------------
            // Blue Lock – Not contract
            //-------------------------------
            [
                'Yoichi', 'Isagi', 13, 'Forward', 400,
                [
                    'speed' => 68, 'stamina' => 70, 'defense' => 18, 'attack' => 32,
                    'shot' => 26, 'pass' => 26, 'dribble' => 24,
                    'block' => 15, 'intercept' => 18, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant au sens du but exceptionnel. Isagi brille par sa lecture du jeu et sa capacité à analyser le terrain pour être toujours au bon endroit au bon moment.'
            ],

            [
                'Meguru', 'Bachira', 13, 'Midfielder', 385,
                [
                    'speed' => 70, 'stamina' => 68, 'defense' => 18, 'attack' => 30,
                    'shot' => 22, 'pass' => 24, 'dribble' => 30,
                    'block' => 15, 'intercept' => 18, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu offensif instinctif, Bachira joue en suivant son instinct et ses dribbles imprévisibles créent le chaos dans les défenses adverses.'
            ],

            [
                'Hyoma', 'Chigiri', 13, 'Forward', 390,
                [
                    'speed' => 84, 'stamina' => 64, 'defense' => 16, 'attack' => 30,
                    'shot' => 24, 'pass' => 20, 'dribble' => 24,
                    'block' => 13, 'intercept' => 16, 'tackle' => 14,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Ailier prodige doté d\'une vitesse à couper le souffle. Fragilisé par une blessure au genou, il se surpasse malgré tout pour devenir un joueur exceptionnel.'
            ],

            [
                'Rensuke', 'Kunigami', 13, 'Forward', 388,
                [
                    'speed' => 66, 'stamina' => 72, 'defense' => 20, 'attack' => 32,
                    'shot' => 28, 'pass' => 19, 'dribble' => 20,
                    'block' => 16, 'intercept' => 17, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant puissant spécialiste des frappes longues. Son tir gauche dévastateur de 27 mètres est sa marque de fabrique. Il rêve de devenir un super-héros du football.'
            ],

            [
                'Seishiro', 'Nagi', 13, 'Forward', 415,
                [
                    'speed' => 68, 'stamina' => 64, 'defense' => 18, 'attack' => 36,
                    'shot' => 28, 'pass' => 24, 'dribble' => 28,
                    'block' => 15, 'intercept' => 17, 'tackle' => 15,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Génie naturel du football découvert par Reo. Ses amortis et son contrôle de balle exceptionnels font de lui un joueur unique, capable de dominer sans effort apparent.'
            ],

            [
                'Reo', 'Mikage', 13, 'Midfielder', 378,
                [
                    'speed' => 64, 'stamina' => 70, 'defense' => 22, 'attack' => 28,
                    'shot' => 20, 'pass' => 28, 'dribble' => 23,
                    'block' => 17, 'intercept' => 20, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Héritier de la Mikage Corporation. Polyvalent et intelligent, il a tout pour réussir et son seul désir est de gagner la Coupe du monde avec Nagi.'
            ],

            [
                'Shoei', 'Baro', 13, 'Forward', 420,
                [
                    'speed' => 70, 'stamina' => 72, 'defense' => 20, 'attack' => 36,
                    'shot' => 30, 'pass' => 16, 'dribble' => 26,
                    'block' => 16, 'intercept' => 17, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Surnommé le Roi du terrain. Avant-centre égoïste et dominant, Baro cherche systématiquement le but par la puissance physique et l\'endurance.'
            ],

            [
                'Rin', 'Itoshi', 13, 'Forward', 435,
                [
                    'speed' => 72, 'stamina' => 74, 'defense' => 22, 'attack' => 38,
                    'shot' => 30, 'pass' => 26, 'dribble' => 28,
                    'block' => 17, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Frère cadet de Sae Itoshi, déterminé à le surpasser. Attaquant complet capable d\'atteindre un état de flow qui le propulse à un niveau supérieur lors des grands matchs.'
            ],

            [
                'Ryosuke', 'Kira', 13, 'Forward', 340,
                [
                    'speed' => 64, 'stamina' => 62, 'defense' => 16, 'attack' => 26,
                    'shot' => 21, 'pass' => 20, 'dribble' => 21,
                    'block' => 13, 'intercept' => 14, 'tackle' => 14,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant de Matsukaze Kokuo invité au Blue Lock. Il sympathise avec Isagi mais est éliminé dès la première épreuve par ce dernier.'
            ],

            [
                'Jingo', 'Raichi', 13, 'Forward', 295,
                [
                    'speed' => 60, 'stamina' => 64, 'defense' => 14, 'attack' => 22,
                    'shot' => 18, 'pass' => 14, 'dribble' => 17,
                    'block' => 12, 'intercept' => 13, 'tackle' => 13,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant grande gueule de Blue Lock. Beau parleur au niveau de jeu modeste, il compense par son énergie et sa détermination affichée.'
            ],

            [
                'Gin', 'Gagamaru', 13, 'Goalkeeper', 350,
                [
                    'speed' => 56, 'stamina' => 66, 'defense' => 24, 'attack' => 12,
                    'shot' => 10, 'pass' => 14, 'dribble' => 12,
                    'block' => 22, 'intercept' => 20, 'tackle' => 16,
                    'hand_save' => 28, 'punch_save' => 26,
                ],
                'Reconverti gardien de but après avoir été attaquant. Sa polyvalence et ses réflexes surprenants lui permettent de réaliser des arrêts décisifs dans les grands matchs.'
            ],

            //-------------------------------
            // Ao Ashi – Not contract
            //-------------------------------
            [
                'Ashito', 'Aoi', 13, 'Defender', 390,
                [
                    'speed' => 72, 'stamina' => 74, 'defense' => 28, 'attack' => 26,
                    'shot' => 20, 'pass' => 22, 'dribble' => 24,
                    'block' => 24, 'intercept' => 26, 'tackle' => 25,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Latéral gauche d\'Esperion, reconverti d\'attaquant par le coach Fukuda. Sa vision exceptionnelle du jeu et sa détermination sans faille compensent son inexpérience au poste défensif.'
            ],

            [
                'Eisaku', 'Ohtomo', 13, 'Midfielder', 355,
                [
                    'speed' => 60, 'stamina' => 70, 'defense' => 26, 'attack' => 24,
                    'shot' => 18, 'pass' => 28, 'dribble' => 20,
                    'block' => 20, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu défensif de l\'équipe B d\'Esperion et premier ami d\'Ashito. Derrière une apparence de couard se cache un meneur composé, salué pour sa vision du jeu et sa capacité à faire le lien.'
            ],

            [
                'Soichiro', 'Tachibana', 13, 'Forward', 360,
                [
                    'speed' => 66, 'stamina' => 66, 'defense' => 16, 'attack' => 30,
                    'shot' => 24, 'pass' => 22, 'dribble' => 22,
                    'block' => 14, 'intercept' => 15, 'tackle' => 15,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Avant-centre de l\'équipe B d\'Esperion, ancien joueur de Tokyo Musashino. Ami d\'Ashito, il apporte de la présence dans la surface et un bon sens du placement.'
            ],

            [
                'Keiji', 'Togashi', 13, 'Defender', 340,
                [
                    'speed' => 58, 'stamina' => 68, 'defense' => 28, 'attack' => 16,
                    'shot' => 13, 'pass' => 17, 'dribble' => 15,
                    'block' => 26, 'intercept' => 22, 'tackle' => 26,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Défenseur central de l\'équipe B d\'Esperion et colocataire d\'Ashito. Solide et discipliné, il assure la stabilité de la ligne arrière par son sens du placement.'
            ],

            [
                'Kanpei', 'Kuroda', 13, 'Midfielder', 345,
                [
                    'speed' => 62, 'stamina' => 68, 'defense' => 24, 'attack' => 25,
                    'shot' => 18, 'pass' => 26, 'dribble' => 21,
                    'block' => 18, 'intercept' => 22, 'tackle' => 20,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu central formé à l\'académie Esperion. Technique et bien formé tactiquement, il apporte de la qualité dans la conservation du ballon et la construction du jeu.'
            ],

            [
                'Jun', 'Asari', 13, 'Defender', 330,
                [
                    'speed' => 66, 'stamina' => 66, 'defense' => 25, 'attack' => 20,
                    'shot' => 15, 'pass' => 20, 'dribble' => 18,
                    'block' => 23, 'intercept' => 21, 'tackle' => 23,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Latéral formé à l\'académie Esperion. Polyvalent et appliqué, il peut évoluer des deux côtés de la défense et participe activement aux phases offensives.'
            ],

            [
                'Yuma', 'Motoki', 13, 'Forward', 395,
                [
                    'speed' => 68, 'stamina' => 68, 'defense' => 17, 'attack' => 32,
                    'shot' => 26, 'pass' => 22, 'dribble' => 24,
                    'block' => 14, 'intercept' => 16, 'tackle' => 15,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant promu en équipe A d\'Esperion. Formé à l\'académie, il a su s\'imposer par sa technique et son efficacité offensive pour franchir le cap vers l\'équipe première.'
            ],

            [
                'Ryuichi', 'Takeshima', 13, 'Defender', 350,
                [
                    'speed' => 60, 'stamina' => 70, 'defense' => 29, 'attack' => 17,
                    'shot' => 13, 'pass' => 17, 'dribble' => 15,
                    'block' => 27, 'intercept' => 23, 'tackle' => 27,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Défenseur central formé à l\'académie Esperion. Solide dans les duels et bien organisé, il est l\'un des piliers de la défense de l\'équipe B.'
            ],

            //-------------------------------
            // Other – Not contract (filler players)
            //-------------------------------
            ['Kenji', 'Nakashima', 13, 'Goalkeeper', 240, [
                'speed' => 52, 'stamina' => 62, 'defense' => 28, 'attack' => 15,
                'shot' => 15, 'pass' => 17, 'dribble' => 15, 'block' => 23,
                'intercept' => 22, 'tackle' => 18, 'hand_save' => 26, 'punch_save' => 24
            ], 'Gardien athlétique au bon sens du placement, fiable dans les situations de un contre un.'],

            ['Ryusei', 'Ogata', 13, 'Goalkeeper', 225, [
                'speed' => 50, 'stamina' => 60, 'defense' => 26, 'attack' => 15,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 22,
                'intercept' => 21, 'tackle' => 17, 'hand_save' => 25, 'punch_save' => 23
            ], 'Gardien calme et constant, apprécié pour sa communication avec sa défense.'],

            ['Hiroki', 'Matsunaga', 13, 'Goalkeeper', 210, [
                'speed' => 48, 'stamina' => 58, 'defense' => 25, 'attack' => 15,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 21,
                'intercept' => 20, 'tackle' => 17, 'hand_save' => 24, 'punch_save' => 22
            ], 'Gardien réflexe, capable de sortir des arrêts décisifs dans les moments importants.'],

            ['Daiki', 'Kuroiwa', 13, 'Goalkeeper', 195, [
                'speed' => 46, 'stamina' => 56, 'defense' => 24, 'attack' => 15,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 21,
                'intercept' => 20, 'tackle' => 16, 'hand_save' => 23, 'punch_save' => 21
            ], 'Gardien travailleur qui compense un manque d\'explosivité par son sérieux et sa rigueur.'],

            ['Sosuke', 'Tamura', 13, 'Goalkeeper', 185, [
                'speed' => 44, 'stamina' => 54, 'defense' => 23, 'attack' => 15,
                'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 20,
                'intercept' => 19, 'tackle' => 16, 'hand_save' => 22, 'punch_save' => 20
            ], 'Gardien discret mais régulier, qui assure l\'essentiel sans prise de risque.'],

            ['Wataru', 'Aoyama', 12, 'Goalkeeper', 175, [
                'speed' => 42, 'stamina' => 52, 'defense' => 22, 'attack' => 15,
                'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 19,
                'intercept' => 19, 'tackle' => 16, 'hand_save' => 21, 'punch_save' => 19
            ], 'Jeune gardien encore en développement, prometteur sur les phases de réflexe.'],

            ['Tomoki', 'Fujioka', 12, 'Goalkeeper', 162, [
                'speed' => 40, 'stamina' => 50, 'defense' => 21, 'attack' => 15,
                'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 19,
                'intercept' => 18, 'tackle' => 15, 'hand_save' => 20, 'punch_save' => 19
            ], 'Gardien de complément, appliqué et discipliné dans son couloir.'],

            ['Nobuki', 'Shimomura', 12, 'Goalkeeper', 148, [
                'speed' => 38, 'stamina' => 48, 'defense' => 20, 'attack' => 15,
                'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 18,
                'intercept' => 18, 'tackle' => 15, 'hand_save' => 19, 'punch_save' => 18
            ], 'Gardien modeste mais sérieux, fait le travail sans briller.'],

            ['Isamu', 'Terada', 12, 'Goalkeeper', 132, [
                'speed' => 36, 'stamina' => 45, 'defense' => 19, 'attack' => 15,
                'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 17,
                'intercept' => 17, 'tackle' => 15, 'hand_save' => 18, 'punch_save' => 17
            ], 'Gardien en début de formation, montre de la volonté mais manque encore d\'expérience.'],

            ['Haruki', 'Enomoto', 12, 'Goalkeeper', 118, [
                'speed' => 34, 'stamina' => 43, 'defense' => 18, 'attack' => 15,
                'shot' => 15, 'pass' => 15, 'dribble' => 15, 'block' => 17,
                'intercept' => 17, 'tackle' => 15, 'hand_save' => 18, 'punch_save' => 17
            ], 'Gardien débutant, encore loin du niveau requis mais plein de bonne volonté.'],

            ['Kazuki', 'Oshiro', 13, 'Defender', 235, [
                'speed' => 56, 'stamina' => 64, 'defense' => 28, 'attack' => 19,
                'shot' => 16, 'pass' => 18, 'dribble' => 16, 'block' => 24,
                'intercept' => 22, 'tackle' => 24, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur central robuste, excelle dans les duels aériens et le marquage serré.'],

            ['Takuto', 'Shinohara', 13, 'Defender', 220, [
                'speed' => 58, 'stamina' => 62, 'defense' => 27, 'attack' => 18,
                'shot' => 15, 'pass' => 17, 'dribble' => 16, 'block' => 23,
                'intercept' => 21, 'tackle' => 23, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur latéral rapide, capable de participer à la relance et de soutenir les phases offensives.'],

            ['Yusuke', 'Nohara', 12, 'Defender', 205, [
                'speed' => 54, 'stamina' => 60, 'defense' => 26, 'attack' => 18,
                'shot' => 15, 'pass' => 17, 'dribble' => 15, 'block' => 22,
                'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur appliqué et discipliné, sécurise son couloir et coupe les trajectoires adverses.'],

            ['Shogo', 'Iwata', 12, 'Defender', 188, [
                'speed' => 50, 'stamina' => 58, 'defense' => 25, 'attack' => 17,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 21,
                'intercept' => 19, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur sobre et peu spectaculaire, mais fiable dans les tâches défensives de base.'],

            ['Rento', 'Kawashima', 12, 'Defender', 170, [
                'speed' => 46, 'stamina' => 55, 'defense' => 23, 'attack' => 16,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 20,
                'intercept' => 18, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur jeune encore en développement, montre de bonnes dispositions défensives.'],

            ['Haruto', 'Mizuno', 13, 'Midfielder', 240, [
                'speed' => 58, 'stamina' => 64, 'defense' => 24, 'attack' => 26,
                'shot' => 22, 'pass' => 24, 'dribble' => 22, 'block' => 19,
                'intercept' => 21, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu box-to-box actif, capable de participer aussi bien à la récupération qu\'à la finition.'],

            ['Sora', 'Takigawa', 13, 'Midfielder', 222, [
                'speed' => 60, 'stamina' => 62, 'defense' => 22, 'attack' => 25,
                'shot' => 21, 'pass' => 23, 'dribble' => 23, 'block' => 18,
                'intercept' => 20, 'tackle' => 19, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu offensif technique, aime s\'infiltrer entre les lignes et décaler ses partenaires.'],

            ['Riku', 'Hasumi', 12, 'Midfielder', 200, [
                'speed' => 55, 'stamina' => 58, 'defense' => 21, 'attack' => 23,
                'shot' => 20, 'pass' => 22, 'dribble' => 21, 'block' => 17,
                'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu polyvalent, fait le lien entre défense et attaque sans éclat particulier mais avec sérieux.'],

            ['Yuto', 'Sekiguchi', 12, 'Midfielder', 182, [
                'speed' => 52, 'stamina' => 56, 'defense' => 20, 'attack' => 22,
                'shot' => 19, 'pass' => 21, 'dribble' => 20, 'block' => 17,
                'intercept' => 18, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu de terrain discret, applique les consignes et maintient l\'équilibre collectif.'],

            ['Kaito', 'Nishida', 12, 'Midfielder', 165, [
                'speed' => 48, 'stamina' => 52, 'defense' => 19, 'attack' => 20,
                'shot' => 18, 'pass' => 20, 'dribble' => 19, 'block' => 16,
                'intercept' => 17, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu en développement, manque encore de régularité mais montre de bonnes intentions.'],

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
            $firstname = $player[0];
            $lastname = $player[1];
            $age = $player[2];
            $position = $player[3];
            $cost = $this->calculateWeeklyCost($player[5], $position);
            $baseStats = $player[5];
            $desc = $player[6] ?? null;

            $fullStats = $this->buildSkills($baseStats, $position);

            // -----------------------------
            // PHOTO AUTO (si fichier existe)
            // -----------------------------
            $slug = Str::slug($firstname . ' ' . $lastname); // ex: taro-misaki

            $headingOverride = $this->getHeadingOverride($slug);
            if ($headingOverride !== null) {
                $fullStats['heading'] = $headingOverride;
            }
            $filename = $slug . '.png';
            $sourcePath = $imagesSourceDir . DIRECTORY_SEPARATOR . $filename;
            $destPath = $storageDir . '/' . $filename; // players/taro-misaki.png
            $photoPathDb = null;
            $specialMoves = $specialMovesByPlayerSlug[$slug] ?? null;
            if (is_file($sourcePath)) {
                // copie uniquement si pas déjà présent (ou tu peux forcer overwrite)
                if (!$storageDisk->exists($destPath)) {
                    $storageDisk->put($destPath, file_get_contents($sourcePath));
                }

                $photoPathDb = $destPath; // stocké en DB
            }

            DB::table('players')->insert([
                'firstname' => $firstname,
                'lastname' => $lastname,
                'age' => $age,
                'position' => $position,
                'cost' => $cost,
                'stats' => json_encode($fullStats, JSON_UNESCAPED_UNICODE),
                'special_moves' => $specialMoves ? json_encode($specialMoves, JSON_UNESCAPED_UNICODE)
                    : null,
                'description' => $desc,
                'photo_path' => $photoPathDb, // null si image absente
            ]);
        }
    }
    /**
     * Génère les stats détaillées à partir des stats de base + position.
     */
    private function buildSkills(array $baseStats, string $position): array
    {
        $speed = $baseStats['speed'];
        $stamina = $baseStats['stamina'];
        $defense = $baseStats['defense'];
        $attack = $baseStats['attack'];

        // valeurs "génériques"
        $shot = (int)round($attack);
        $pass = (int)round($attack * 0.7 + $speed * 0.3);
        $dribble = (int)round($attack * 0.7 + $speed * 0.3);
        $block = (int)round($defense * 0.8 + $stamina * 0.2);
        $intercept = (int)round($defense * 0.7 + $speed * 0.3);
        $tackle = (int)round($defense * 0.9 + $stamina * 0.1);

        // par défaut, les joueurs de champ ne sont pas bons gardiens
        $handSave = max(5, (int)round($defense * 0.2));
        $punchSave = max(5, (int)round($defense * 0.15));

        // par défaut, faible (attaquants/gardiens n'interviennent pas sur les centres)
        $heading = 15;

        switch ($position) {
            case 'Forward':
                $shot = (int)round(min(100, $attack * 1.05));
                $dribble = (int)round(min(100, ($attack * 0.8 + $speed * 0.4) / 1.1));
                $pass = (int)round(($attack * 0.75 + $speed * 0.35) / 1.1);
                break;

            case 'Midfielder':
                $pass = (int)round(min(100, ($attack * 0.9 + $speed * 0.4) / 1.1));
                $dribble = (int)round(min(100, ($attack * 0.85 + $speed * 0.4) / 1.1));
                $heading = (int)round($defense * 0.3 + $block * 0.2 + $stamina * 0.1) + 15;
                break;

            case 'Defender':
                $block = (int)round(min(100, $block * 1.05));
                $tackle = (int)round(min(100, $tackle * 1.05));
                $intercept = (int)round(($defense * 0.8 + $speed * 0.3) / 1.1);
                $heading = (int)round(min(100, $defense * 0.5 + $block * 0.3 + $stamina * 0.2));
                break;

            case 'Goalkeeper':
                // les GK sont spéciaux
                $shot = (int)round($attack * 0.8);
                $pass = (int)round($attack * 0.7 + $speed * 0.3);
                $dribble = (int)round($speed * 0.7 + $defense * 0.3);

                $block = (int)round(($defense * 0.9 + $stamina * 0.3) / 1.2);
                $intercept = (int)round($defense * 0.8 + $speed * 0.2);
                $tackle = (int)round($defense * 0.7 + $stamina * 0.3);

                $handSave = (int)round(min(100, ($defense * 1.3 + $stamina * 0.5) / 1.5));
                $punchSave = (int)round(min(100, ($defense * 1.1 + $stamina * 0.7) / 1.5));
                break;
        }

        return $baseStats + [
                'shot' => $shot,
                'pass' => $pass,
                'dribble' => $dribble,
                'block' => $block,
                'intercept' => $intercept,
                'tackle' => $tackle,
                'heading' => $heading,
                'hand_save' => $handSave,
                'punch_save' => $punchSave,
            ];
    }

    /**
     * Overrides manuels de la stat heading pour des joueurs connus
     * (slug "firstname-lastname"), au-delà du calcul automatique.
     */
    private function getHeadingOverride(string $slug): ?int
    {
        $overrides = [
            'jito' => 42,
            'soda' => 40,
            'matsuyama' => 35,
        ];

        foreach ($overrides as $needle => $value) {
            if (str_contains($slug, $needle)) {
                return $value;
            }
        }

        return null;
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
                'Yuzo', 'Morisaki', 12, 'Goalkeeper', 340,
                [
                    'speed' => 60, 'stamina' => 80, 'defense' => 32, 'attack' => 18,
                    'shot' => 16, 'pass' => 18, 'dribble' => 16,
                    'block' => 18, 'intercept' => 20, 'tackle' => 18,
                    'hand_save' => 27, 'punch_save' => 24
                ],
                'Gardien titulaire de Nankatsu. Sérieux et travailleur, Morisaki compense son manque de talent naturel par une grande endurance et une forte discipline.'
            ],

            [
                'Masato', 'Nakazato', 12, 'Defender', 240,
                [
                    'speed' => 45, 'stamina' => 55, 'defense' => 22, 'attack' => 18,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 20, 'intercept' => 18, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur discret de Nankatsu, Nakazato joue un rôle simple et efficace. Il assure le marquage et soutient la ligne arrière sans prendre de risques.'
            ],

            [
                'Ryo', 'Ishizaki', 12, 'Defender', 425,
                [
                    'speed' => 65, 'stamina' => 75, 'defense' => 31, 'attack' => 22,
                    'shot' => 18, 'pass' => 23, 'dribble' => 18,
                    'block' => 25, 'intercept' => 24, 'tackle' => 27,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur emblématique de Nankatsu, Ishizaki est connu pour son courage, ses interventions désespérées et son célèbre « face block ». Son mental compense largement ses limites techniques.'
            ],

            [
                'Hiroshi', 'Nagano', 12, 'Defender', 235,
                [
                    'speed' => 43, 'stamina' => 58, 'defense' => 23, 'attack' => 17,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 21, 'intercept' => 19, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur de soutien, Nagano fait partie des joueurs de rotation de Nankatsu. Il applique les consignes et renforce la défense collective.'
            ],

            [
                'Manabu', 'Okawa', 12, 'Defender', 230,
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
                    'speed' => 50, 'stamina' => 60, 'defense' => 20, 'attack' => 22,
                    'shot' => 20, 'pass' => 22, 'dribble' => 20,
                    'block' => 18, 'intercept' => 19, 'tackle' => 18,
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
                'Tsubasa', 'Ozora', 12, 'Midfielder', 500,
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
                'Kazuma', 'Matsumo', 12, 'Defender', 225,
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
                'Jun', 'Kurata', 12, 'Midfielder', 260,
                [
                    'speed' => 52, 'stamina' => 63, 'defense' => 21, 'attack' => 23,
                    'shot' => 20, 'pass' => 22, 'dribble' => 21,
                    'block' => 18, 'intercept' => 19, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain polyvalent, Kurata assure la transition défense-attaque. Il oriente le jeu de manière simple et soutient le pressing au milieu.'
            ],

            [
                'Takumi', 'Osaki', 12, 'Midfielder', 270,
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
                'Kaito', 'Inamura', 12, 'Midfielder', 290,
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
                'Shinji', 'Noda', 12, 'Midfielder', 230,
                [
                    'speed' => 57, 'stamina' => 66, 'defense' => 26, 'attack' => 22,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain travailleur, Noda soutient la récupération et relance de manière simple. Il privilégie le collectif et l’équilibre.'
            ],

            [
                'Tsutomu', 'Nagaoka', 12, 'Midfielder', 225,
                [
                    'speed' => 56, 'stamina' => 65, 'defense' => 26, 'attack' => 21,
                    'shot' => 20, 'pass' => 22, 'dribble' => 20,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Nagaoka assure la continuité du jeu et renforce le bloc compact de Hirado.'
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
                'Koji', 'Ishikawa', 12, 'Midfielder', 240,
                [
                    'speed' => 48, 'stamina' => 59, 'defense' => 21, 'attack' => 21,
                    'shot' => 20, 'pass' => 21, 'dribble' => 20,
                    'block' => 19, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu travailleur, Ishikawa soutient la récupération et participe à la conservation du ballon.'
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

            [
                'Junichi', 'Nagasaki', 12, 'Forward', 250,
                [
                    'speed' => 59, 'stamina' => 60, 'defense' => 20, 'attack' => 26,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Avant-centre de Shimada, Nagasaki est un finisseur correct qui tente de convertir les rares occasions.'
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
                'Naoki', 'Wesugi', 12, 'Forward', 250,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 20, 'attack' => 25,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de complément, Wesugi accompagne Nagasaki et tente d’exister par son activité.'
            ],


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

            // Hungry Heart – Not contract
            [
                'Kyosuke', 'Kojima', 16, 'Forward', 520,
                [
                    'speed' => 82, 'stamina' => 78, 'defense' => 24, 'attack' => 44,
                    'shot' => 36, 'pass' => 28, 'dribble' => 32,
                    'block' => 18, 'intercept' => 20, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10
                ],
                'Héros de Hungry Heart, attaquant fougueux doté d’un tir puissant et d’une détermination explosive.'
            ],

            [
                'Seisuke', 'Kojima', 17, 'Forward', 540,
                [
                    'speed' => 78, 'stamina' => 82, 'defense' => 26, 'attack' => 42,
                    'shot' => 34, 'pass' => 30, 'dribble' => 30,
                    'block' => 18, 'intercept' => 21, 'tackle' => 19,
                    'hand_save' => 10, 'punch_save' => 10
                ],
                'Frère adoptif de Kyosuke, joueur talentueux et calme, doté d’une excellente vision offensive.'
            ],

            [
                'Rodrigo', 'Santamaria', 17, 'Midfielder', 495,
                [
                    'speed' => 75, 'stamina' => 80, 'defense' => 28, 'attack' => 38,
                    'shot' => 30, 'pass' => 33, 'dribble' => 31,
                    'block' => 20, 'intercept' => 24, 'tackle' => 22,
                    'hand_save' => 10, 'punch_save' => 10
                ],
                'Milieu offensif technique et créatif, véritable moteur du jeu collectif.'
            ],

            [
                'Hideto', 'Tobi', 16, 'Midfielder', 460,
                [
                    'speed' => 82, 'stamina' => 75, 'defense' => 24, 'attack' => 34,
                    'shot' => 27, 'pass' => 28, 'dribble' => 34,
                    'block' => 18, 'intercept' => 22, 'tackle' => 20,
                    'hand_save' => 10, 'punch_save' => 10
                ],
                'Ailier rapide et fantasque, capable de déborder et créer des différences en un contre un.'
            ],

            [
                'Kazuhiko', 'Mori', 17, 'Goalkeeper', 480,
                [
                    'speed' => 62, 'stamina' => 78, 'defense' => 36, 'attack' => 18,
                    'shot' => 15, 'pass' => 18, 'dribble' => 16,
                    'block' => 27, 'intercept' => 26, 'tackle' => 22,
                    'hand_save' => 34, 'punch_save' => 32
                ],
                'Gardien fiable doté d’un sens du placement remarquable.'
            ],

            [
                'Haruma', 'Katori', 17, 'Midfielder', 430,
                [
                    'speed' => 68, 'stamina' => 80, 'defense' => 32, 'attack' => 28,
                    'shot' => 22, 'pass' => 26, 'dribble' => 25,
                    'block' => 24, 'intercept' => 26, 'tackle' => 27,
                    'hand_save' => 10, 'punch_save' => 10
                ],
                'Milieu défensif solide, infatigable et précieux dans la récupération.'
            ],

            [
                'Jin', 'Kano', 17, 'Defender', 410,
                [
                    'speed' => 62, 'stamina' => 75, 'defense' => 34, 'attack' => 22,
                    'shot' => 18, 'pass' => 20, 'dribble' => 19,
                    'block' => 30, 'intercept' => 26, 'tackle' => 32,
                    'hand_save' => 10, 'punch_save' => 10
                ],
                'Défenseur central agressif et physique, pilier de la ligne arrière.'
            ],

            [
                'Ryo', 'Yoshijogi', 17, 'Defender', 395,
                [
                    'speed' => 60, 'stamina' => 70, 'defense' => 32, 'attack' => 20,
                    'shot' => 16, 'pass' => 19, 'dribble' => 18,
                    'block' => 28, 'intercept' => 25, 'tackle' => 29,
                    'hand_save' => 10, 'punch_save' => 10
                ],
                'Défenseur calme et discipliné, complément idéal de Kano en défense.'
            ],

            // Blue Lock – Not contract
            [
                'Yoichi', 'Isagi', 16, 'Forward', 520,
                [
                    'speed'      => 78, 'stamina'    => 80, 'defense'    => 22, 'attack'     => 44,
                    'shot'       => 34, 'pass'       => 32, 'dribble'    => 30,
                    'block'      => 18, 'intercept'  => 22, 'tackle'     => 20,
                    'hand_save'  => 10, 'punch_save' => 10,
                ],
                'Attaquant au sens du but exceptionnel, Isagi brille par sa lecture du jeu et sa capacité à être au bon endroit au bon moment.'
            ],

            [
                'Meguru', 'Bachira', 16, 'Midfielder', 500,
                [
                    'speed'      => 80, 'stamina'    => 78, 'defense'    => 22, 'attack'     => 40,
                    'shot'       => 30, 'pass'       => 30, 'dribble'    => 36,
                    'block'      => 18, 'intercept'  => 22, 'tackle'     => 20,
                    'hand_save'  => 10, 'punch_save' => 10,
                ],
                'Milieu offensif fantasque, Bachira adore le dribble et le jeu instinctif, capable de créer le chaos dans les défenses.'
            ],

            [
                'Hyoma', 'Chigiri', 16, 'Forward', 510,
                [
                    'speed'      => 90, 'stamina'    => 76, 'defense'    => 20, 'attack'     => 40,
                    'shot'       => 32, 'pass'       => 26, 'dribble'    => 30,
                    'block'      => 16, 'intercept'  => 20, 'tackle'     => 18,
                    'hand_save'  => 10, 'punch_save' => 10,
                ],
                'Ailier ultra-rapide, Chigiri mise sur ses accélérations et ses appels pour prendre la profondeur et surprendre la défense.'
            ],

            [
                'Rensuke', 'Kunigami', 17, 'Forward', 505,
                [
                    'speed'      => 76, 'stamina'    => 82, 'defense'    => 24, 'attack'     => 42,
                    'shot'       => 35, 'pass'       => 25, 'dribble'    => 26,
                    'block'      => 19, 'intercept'  => 21, 'tackle'     => 22,
                    'hand_save'  => 10, 'punch_save' => 10,
                ],
                'Attaquant puissant au tir dévastateur, Kunigami combine impact physique et présence dans la surface.'
            ],

            [
                'Seishiro', 'Nagi', 16, 'Forward', 540,
                [
                    'speed'      => 78, 'stamina'    => 74, 'defense'    => 22, 'attack'     => 46,
                    'shot'       => 36, 'pass'       => 30, 'dribble'    => 34,
                    'block'      => 18, 'intercept'  => 21, 'tackle'     => 19,
                    'hand_save'  => 10, 'punch_save' => 10,
                ],
                'Génie naturel du football, Nagi possède un contrôle de balle et une première touche exceptionnels.'
            ],

            [
                'Reo', 'Mikage', 16, 'Midfielder', 495,
                [
                    'speed'      => 74, 'stamina'    => 80, 'defense'    => 26, 'attack'     => 38,
                    'shot'       => 28, 'pass'       => 34, 'dribble'    => 29,
                    'block'      => 20, 'intercept'  => 24, 'tackle'     => 22,
                    'hand_save'  => 10, 'punch_save' => 10,
                ],
                'Milieu polyvalent et intelligent, Reo sait s’adapter à tous les rôles pour sublimer ses coéquipiers.'
            ],

            [
                'Shoei', 'Barou', 17, 'Forward', 550,
                [
                    'speed'      => 80, 'stamina'    => 82, 'defense'    => 24, 'attack'     => 48,
                    'shot'       => 38, 'pass'       => 22, 'dribble'    => 32,
                    'block'      => 19, 'intercept'  => 21, 'tackle'     => 22,
                    'hand_save'  => 10, 'punch_save' => 10,
                ],
                'Avant-centre égoïste et dominant, Barou impose sa loi physiquement et cherche systématiquement le but.'
            ],

            [
                'Rin', 'Itoshi', 17, 'Forward', 560,
                [
                    'speed'      => 82, 'stamina'    => 84, 'defense'    => 26, 'attack'     => 50,
                    'shot'       => 40, 'pass'       => 32, 'dribble'    => 34,
                    'block'      => 20, 'intercept'  => 24, 'tackle'     => 24,
                    'hand_save'  => 10, 'punch_save' => 10,
                ],
                'Attaquant prodige complet, Rin combine technique, vision et mental de tueur pour dominer la surface.'
            ],

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
            $firstname = $player[0];
            $lastname = $player[1];
            $age = $player[2];
            $position = $player[3];
            $cost = $player[4];
            $baseStats = $player[5];
            $desc = $player[6] ?? null;

            $fullStats = $this->buildSkills($baseStats, $position);

            // -----------------------------
            // PHOTO AUTO (si fichier existe)
            // -----------------------------
            $slug = Str::slug($firstname . ' ' . $lastname); // ex: taro-misaki
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

        switch ($position) {
            case 'Forward':
                $shot = (int)round(min(100, $attack * 1.05));
                $dribble = (int)round(min(100, ($attack * 0.8 + $speed * 0.4) / 1.1));
                $pass = (int)round(($attack * 0.75 + $speed * 0.35) / 1.1);
                break;

            case 'Midfielder':
                $pass = (int)round(min(100, ($attack * 0.9 + $speed * 0.4) / 1.1));
                $dribble = (int)round(min(100, ($attack * 0.85 + $speed * 0.4) / 1.1));
                break;

            case 'Defender':
                $block = (int)round(min(100, $block * 1.05));
                $tackle = (int)round(min(100, $tackle * 1.05));
                $intercept = (int)round(($defense * 0.8 + $speed * 0.3) / 1.1);
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
                'hand_save' => $handSave,
                'punch_save' => $punchSave,
            ];
    }
}

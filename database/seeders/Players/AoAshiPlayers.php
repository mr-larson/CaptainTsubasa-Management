<?php

namespace Database\Seeders\Players;

/**
 * Données joueurs (et special moves) — groupe : AoAshiPlayers.
 * Extrait de PlayerSeeder pour alléger et organiser par source.
 */
class AoAshiPlayers
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            //-------------------------------
            // Ao Ashi – Not contract
            //-------------------------------
            [
                'Ashito', 'Aoi', 13, 'Defender', 390,
                [
                    'speed' => 72, 'stamina' => 74, 'defense' => 28, 'attack' => 26,
                    'shot' => 20, 'pass' => 23, 'dribble' => 17,
                    'block' => 17, 'intercept' => 26, 'tackle' => 25,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Latéral gauche d\'Esperion, reconverti d\'attaquant par le coach Fukuda. Sa vision exceptionnelle du jeu et sa détermination sans faille compensent son inexpérience au poste défensif.',
                ['Forward']
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

        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public static function specialMoves(): array
    {
        return [
            // AO ASHI
            // =======================

            'ashito-aoi' => [[
                'key'         => 'aoi_bird_eye_view',
                'mode'        => 'attack',
                'label'       => 'Vision panoramique',
                'short_label' => 'Vision',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Sa vue d’ensemble du terrain, « l’œil d’oiseau », lui permet de lancer une passe décisive depuis l’arrière.',
            ]],

            'eisaku-ohtomo' => [[
                'key'         => 'ohtomo_link_play',
                'mode'        => 'attack',
                'label'       => 'Jeu de liaison',
                'short_label' => 'Liaison',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Une relance calme et bien orientée qui fait le lien entre la défense et l’attaque.',
            ]],

            'soichiro-tachibana' => [[
                'key'         => 'soichiro_box_finish',
                'mode'        => 'attack',
                'label'       => 'Renard des surfaces',
                'short_label' => 'Renard',
                'cooldown'    => 2,
                'base_action' => 'shot',
                'description' => 'Un excellent sens du placement dans la surface pour conclure au bon moment.',
            ]],

            'keiji-togashi' => [[
                'key'         => 'togashi_iron_marking',
                'mode'        => 'defense',
                'label'       => 'Marquage de fer',
                'short_label' => 'Marquage',
                'cooldown'    => 2,
                'base_action' => 'tackle',
                'description' => 'Défenseur central discipliné, solide dans le timing du tacle et le sens du placement.',
            ]],

            'kanpei-kuroda' => [[
                'key'         => 'kuroda_ball_control',
                'mode'        => 'attack',
                'label'       => 'Conservation technique',
                'short_label' => 'Conservation',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Technique et bien formé tactiquement, il garde le ballon sous pression et construit le jeu.',
            ]],

            'jun-asari' => [[
                'key'         => 'asari_overlap_run',
                'mode'        => 'attack',
                'label'       => 'Montée du latéral',
                'short_label' => 'Montée',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Latéral polyvalent qui se projette des deux côtés pour soutenir l’attaque.',
            ]],

            'yuma-motoki' => [[
                'key'         => 'motoki_clinical_finish',
                'mode'        => 'attack',
                'label'       => 'Finition d’académie',
                'short_label' => 'Finition',
                'cooldown'    => 2,
                'base_action' => 'shot',
                'description' => 'Attaquant techniquement formé à l’académie, efficace et précis devant le but.',
            ]],

            'ryuichi-takeshima' => [[
                'key'         => 'takeshima_wall_block',
                'mode'        => 'defense',
                'label'       => 'Mur central',
                'short_label' => 'Mur',
                'cooldown'    => 2,
                'base_action' => 'block',
                'description' => 'Solide dans les duels et bien organisé, il ferme l’axe et bloque les frappes.',
            ]],

            // =======================
        ];
    }
}

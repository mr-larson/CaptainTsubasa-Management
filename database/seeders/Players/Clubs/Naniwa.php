<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Naniwa (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Naniwa
{
    /**
     * Titulaires de Naniwa, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
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
        ];
    }

    /**
     * Remplaçants de Naniwa : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
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
        ];
    }

    /**
     * Coups spéciaux des joueurs de Naniwa, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [
            'taichi-nakanishi' => [[
                'key'         => 'nakanishi_giant_wall',
                'mode'        => 'defense',
                'label'       => 'Mur géant',
                'short_label' => 'Géant',
                'cooldown'    => 2,
                'base_action' => 'hand_save',
                'description' => 'Le gardien géant ferme entièrement l’axe et bloque un tir à bout portant.',
            ]],

            // =======================
        ];
    }
}

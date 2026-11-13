<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Nakahara (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Nakahara
{
    /**
     * Titulaires de Nakahara, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
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
                'Kohei', 'Hori', 12, 'Defender', 250,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 25, 'attack' => 20,
                    'shot' => 21, 'pass' => 24, 'dribble' => 21,
                    'block' => 22, 'intercept' => 25, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur, Kohei participe à la construction du jeu et soutient les attaques lorsqu’une opportunité se présente.'
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
                'Takashi', 'Hatori', 12, 'Forward', 250,
                [
                    'speed' => 49, 'stamina' => 60, 'defense' => 20, 'attack' => 26,
                    'shot' => 24, 'pass' => 24, 'dribble' => 22,
                    'block' => 17, 'intercept' => 18, 'tackle' => 17,
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
                'Prodige solitaire de Nakahara, Aoi est un attaquant technique et imprévisible. Doté d’un talent exceptionnel, il peut à lui seul faire basculer un match malgré la faiblesse de son équipe.',
                ['Defender', 'Midfielder']
            ],
        ];
    }

    /**
     * Remplaçants de Nakahara : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
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
        ];
    }

    /**
     * Coups spéciaux des joueurs de Nakahara, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [
            'shingo-aoi' => [[
                'key'         => 'aoi_euro_step',
                'mode'        => 'attack',
                'label'       => 'Dribble européen',
                'short_label' => 'Euro',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Un dribble inspiré du football européen pour éliminer son adversaire direct.',
            ]],
        ];
    }
}

<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Minawi (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Minawi
{
    /**
     * Titulaires de Minawi, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
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
        ];
    }

    /**
     * Remplaçants de Minawi : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
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
        ];
    }

    /**
     * Coups spéciaux des joueurs de Minawi, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [];
    }
}

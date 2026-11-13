<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Furano (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Furano
{
    /**
     * Titulaires de Furano, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
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
                'Capitaine de Furano, Matsuyama est un leader infatigable. Son endurance exceptionnelle, sa rigueur défensive et son mental font de lui le pilier de l’équipe.',
                ['Forward', 'Defender'],
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
        ];
    }

    /**
     * Remplaçants de Furano : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
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
        ];
    }

    /**
     * Coups spéciaux des joueurs de Furano, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [
            'hikaru-matsuyama' => [[
                'key'         => 'matsuyama_eagle_shot',
                'mode'        => 'attack',
                'label'       => 'Tir de l’Aigle',
                'short_label' => 'Aigle',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Un tir puissant frappé à moyenne distance, symbole de son leadership.',
            ]],
        ];
    }
}

<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Nankatsu (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Nankatsu
{
    /**
     * Titulaires de Nankatsu, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
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
                'Prodige du football japonais, Tsubasa est le cœur et l’âme de Nankatsu. Doté d’une vision exceptionnelle et d’une technique complète, il élève le niveau de toute l’équipe.',
                ['Midfielder', 'Defender'],
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
        ];
    }

    /**
     * Remplaçants de Nankatsu : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
            ['Kenji', 'Tomo', 12, 'Midfielder', 180, [
                'speed' => 45, 'stamina' => 65, 'defense' => 20, 'attack' => 22,
                'shot' => 20, 'pass' => 21, 'dribble' => 20, 'block' => 17,
                'intercept' => 18, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Remplaçant polyvalent de Nankatsu.'],
        ];
    }

    /**
     * Coups spéciaux des joueurs de Nankatsu, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [
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
        ];
    }
}

<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Hanawa (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Hanawa
{
    /**
     * Titulaires de Hanawa, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
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
                'L’un des célèbres frères Tachibana. Spécialiste du jeu aérien et des combinaisons acrobatiques, Masao est une menace constante dans la surface.',
                ['Midfielder', 'Defender'],
            ],

            [
                'Kazuo', 'Tachibana', 13, 'Forward', 425,
                [
                    'speed' => 75, 'stamina' => 75, 'defense' => 20, 'attack' => 38,
                    'shot' => 29, 'pass' => 28, 'dribble' => 26,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Second des frères Tachibana, Kazuo forme avec Masao un duo redoutable. Leur synchronisation parfaite et leurs techniques aériennes font l’identité offensive de Hanawa.',
                ['Midfielder', 'Defender'],
            ],
        ];
    }

    /**
     * Remplaçants de Hanawa : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
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
        ];
    }

    /**
     * Coups spéciaux des joueurs de Hanawa, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [
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
        ];
    }
}

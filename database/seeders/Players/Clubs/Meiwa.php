<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Meiwa (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Meiwa
{
    /**
     * Titulaires de Meiwa, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
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
        ];
    }

    /**
     * Remplaçants de Meiwa : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
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
        ];
    }

    /**
     * Coups spéciaux des joueurs de Meiwa, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [];
    }
}

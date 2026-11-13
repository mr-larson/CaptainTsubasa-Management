<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Azuma-ichi (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Azumaichi
{
    /**
     * Titulaires de Azuma-ichi, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
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
        ];
    }

    /**
     * Remplaçants de Azuma-ichi : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
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
        ];
    }

    /**
     * Coups spéciaux des joueurs de Azuma-ichi, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [
            'makoto-soda' => [[
                'key'         => 'soda_kazaguruma_tackle',
                'mode'        => 'defense',
                'label'       => 'Tacle moulinet',
                'short_label' => 'Moulinet',
                'cooldown'    => 3,
                'base_action' => 'tackle',
                'description' => 'Un tacle violent en rotation qui fauche tout sur son passage.',
            ]],
        ];
    }
}

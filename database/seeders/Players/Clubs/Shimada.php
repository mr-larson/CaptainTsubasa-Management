<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Shimada (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Shimada
{
    /**
     * Titulaires de Shimada, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
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
                'Koji', 'Ishikawa', 12, 'Defender', 240,
                [
                    'speed' => 48, 'stamina' => 59, 'defense' => 21, 'attack' => 15,
                    'shot' => 14, 'pass' => 21, 'dribble' => 20,
                    'block' => 21, 'intercept' => 22, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur travailleur, Ishikawa soutient la récupération et participe à la conservation du ballon.'
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
                'Junichi', 'Nagasaki', 12, 'Midfielder', 250,
                [
                    'speed' => 59, 'stamina' => 60, 'defense' => 23, 'attack' => 26,
                    'shot' => 24, 'pass' => 26, 'dribble' => 26,
                    'block' => 20, 'intercept' => 25, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Avant-centre de Shimada, Nagasaki est un finisseur correct qui tente de convertir les rares occasions.'
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
                'Naoki', 'Wesugi', 12, 'Midfielder', 250,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 20, 'attack' => 25,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de complément, Wesugi accompagne Nagasaki et tente d’exister par son activité.'
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
        ];
    }

    /**
     * Remplaçants de Shimada : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
            ['Hiroshi', 'Okamoto', 12, 'Defender', 155, [
                'speed' => 41, 'stamina' => 57, 'defense' => 23, 'attack' => 17,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 20,
                'intercept' => 18, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Shimada.'],
            ['Kenji', 'Tsuda', 12, 'Midfielder', 160, [
                'speed' => 43, 'stamina' => 58, 'defense' => 20, 'attack' => 20,
                'shot' => 19, 'pass' => 20, 'dribble' => 19, 'block' => 17,
                'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Shimada.'],
            ['Takuya', 'Nozaki', 12, 'Forward', 165, [
                'speed' => 47, 'stamina' => 58, 'defense' => 15, 'attack' => 22,
                'shot' => 21, 'pass' => 17, 'dribble' => 19, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Shimada.'],
        ];
    }

    /**
     * Coups spéciaux des joueurs de Shimada, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [];
    }
}

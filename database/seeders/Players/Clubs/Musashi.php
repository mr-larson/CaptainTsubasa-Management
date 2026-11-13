<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Musashi (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Musashi
{
    /**
     * Titulaires de Musashi, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
            [
                'Tsutomu', 'Moriyama', 12, 'Goalkeeper', 300,
                [
                    'speed' => 50, 'stamina' => 70, 'defense' => 30, 'attack' => 18,
                    'shot' => 16, 'pass' => 18, 'dribble' => 15,
                    'block' => 22, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 26, 'punch_save' => 23
                ],
                'Gardien sérieux de Musashi, Moriyama se distingue par son calme et son sens du placement. Il n’est pas spectaculaire, mais fiable dans un système défensif rigoureux.'
            ],

            [
                'Osamu', 'Kido', 12, 'Defender', 290,
                [
                    'speed' => 48, 'stamina' => 68, 'defense' => 29, 'attack' => 22,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 22, 'intercept' => 23, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur discipliné, Kido applique strictement les consignes tactiques de Musashi. Il privilégie l’anticipation et le jeu sans ballon.'
            ],

            [
                'Hiroshi', 'Mukai', 12, 'Defender', 280,
                [
                    'speed' => 46, 'stamina' => 66, 'defense' => 28, 'attack' => 21,
                    'shot' => 17, 'pass' => 18, 'dribble' => 16,
                    'block' => 23, 'intercept' => 22, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur appliqué, Mukai assure les couvertures et le maintien du bloc défensif. Il incarne la rigueur collective de Musashi.'
            ],

            [
                'Ryoichi', 'Sano', 12, 'Defender', 275,
                [
                    'speed' => 45, 'stamina' => 65, 'defense' => 27, 'attack' => 21,
                    'shot' => 17, 'pass' => 18, 'dribble' => 16,
                    'block' => 23, 'intercept' => 22, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur méthodique, Sano complète la ligne arrière par sa constance et son sens du placement.'
            ],

            [
                'Shinichi', 'Suzuki', 12, 'Midfielder', 295,
                [
                    'speed' => 49, 'stamina' => 69, 'defense' => 26, 'attack' => 24,
                    'shot' => 22, 'pass' => 24, 'dribble' => 22,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain équilibré, Suzuki assure la circulation du ballon et soutient le jeu collectif voulu par Musashi.'
            ],

            [
                'Kensaku', 'Yoshida', 12, 'Midfielder', 285,
                [
                    'speed' => 47, 'stamina' => 67, 'defense' => 25, 'attack' => 23,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Yoshida privilégie la sécurité et l’équilibre. Il participe à la construction sans prendre de risques.'
            ],

            [
                'Shota', 'Inoue', 12, 'Midfielder', 290,
                [
                    'speed' => 48, 'stamina' => 68, 'defense' => 25, 'attack' => 23,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu travailleur, Inoue renforce la densité du milieu de terrain et applique strictement les schémas tactiques.'
            ],

            [
                'Jun', 'Misugi', 13, 'Midfielder', 500,
                [
                    'speed' => 83, 'stamina' => 50, 'defense' => 38, 'attack' => 38,
                    'shot' => 29, 'pass' => 29, 'dribble' => 29,
                    'block' => 25, 'intercept' => 25, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Surnommé le « Prince du terrain », Misugi est un génie tactique et technique. Freiné par une santé fragile, il compense par une intelligence de jeu exceptionnelle et une maîtrise totale du rythme.',
                ['Forward', 'Defender'],
            ],

            [
                'Minoru', 'Honma', 12, 'Forward', 350,
                [
                    'speed' => 65, 'stamina' => 75, 'defense' => 20, 'attack' => 31,
                    'shot' => 26, 'pass' => 22, 'dribble' => 23,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant principal de Musashi, Honma se montre efficace dans la surface et sait exploiter les occasions créées par Misugi.',
                ['Midfielder'],
            ],

            [
                'Akira', 'Ichinose', 12, 'Forward', 350,
                [
                    'speed' => 70, 'stamina' => 67, 'defense' => 18, 'attack' => 32,
                    'shot' => 27, 'pass' => 21, 'dribble' => 24,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant mobile, Ichinose apporte de la profondeur et des appels constants pour dynamiser l’attaque de Musashi.'
            ],

            [
                'Shinji', 'Sanada', 12, 'Forward', 350,
                [
                    'speed' => 65, 'stamina' => 72, 'defense' => 19, 'attack' => 30,
                    'shot' => 25, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de soutien, Sanada complète le trio offensif par son activité et son sens du placement.'
            ],
        ];
    }

    /**
     * Remplaçants de Musashi : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
            ['Hiroyuki', 'Noda', 12, 'Defender', 160, [
                'speed' => 42, 'stamina' => 62, 'defense' => 25, 'attack' => 18,
                'shot' => 16, 'pass' => 17, 'dribble' => 15, 'block' => 21,
                'intercept' => 19, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Musashi.'],
            ['Junpei', 'Aoki', 12, 'Midfielder', 165, [
                'speed' => 44, 'stamina' => 63, 'defense' => 21, 'attack' => 20,
                'shot' => 19, 'pass' => 21, 'dribble' => 19, 'block' => 17,
                'intercept' => 18, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Musashi.'],
            ['Satoru', 'Koyama', 12, 'Forward', 170, [
                'speed' => 57, 'stamina' => 61, 'defense' => 16, 'attack' => 23,
                'shot' => 22, 'pass' => 18, 'dribble' => 20, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Musashi.'],
        ];
    }

    /**
     * Coups spéciaux des joueurs de Musashi, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [
            'jun-misugi' => [
                [
                    'key'         => 'misugi_field_prince',
                    'mode'        => 'attack',
                    'label'       => 'Prince du terrain',
                    'short_label' => 'Prince',
                    'cooldown'    => 3,
                    'base_action' => 'dribble',
                    'description' => 'Une action où Misugi dicte le tempo et délivre une passe décisive parfaite.',
                ],
                [
                    'key'         => 'misugi_elegant_block',
                    'mode'        => 'defense',
                    'label'       => 'Mur Miraculeux',
                    'short_label' => 'Miracle',
                    'cooldown'    => 3,
                    'base_action' => 'intercept',
                    'description' => 'Misugi anticipe parfaitement la passe adverse et intercepte le ballon avec une élégance inégalée.',
                ],
            ],
        ];
    }
}

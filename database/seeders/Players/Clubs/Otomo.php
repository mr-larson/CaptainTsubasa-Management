<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Otomo (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Otomo
{
    /**
     * Titulaires de Otomo, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
            [
                'Isamu', 'Ichijo', 12, 'Goalkeeper', 275,
                [
                    'speed' => 50, 'stamina' => 65, 'defense' => 29, 'attack' => 18,
                    'shot' => 16, 'pass' => 18, 'dribble' => 15,
                    'block' => 21, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 24, 'punch_save' => 22
                ],
                'Gardien d’Otomo, Ichijo est un portier sérieux qui mise sur le placement et la discipline défensive. Il s’appuie sur un bloc arrière très compact.'
            ],

            [
                'Masaki', 'Yoshikawa', 12, 'Defender', 285,
                [
                    'speed' => 52, 'stamina' => 67, 'defense' => 30, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur rigoureux, Yoshikawa applique un marquage strict et privilégie la sécurité défensive.'
            ],

            [
                'Koji', 'Nishio', 12, 'Defender', 350,
                [
                    'speed' => 55, 'stamina' => 70, 'defense' => 34, 'attack' => 22,
                    'shot' => 19, 'pass' => 22, 'dribble' => 19,
                    'block' => 26, 'intercept' => 27, 'tackle' => 27,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Pilier défensif d’Otomo, Nishio est un stoppeur puissant et constant. Il excelle dans les duels et la récupération.'
            ],

            [
                'Masao', 'Nakayama', 12, 'Defender', 350,
                [
                    'speed' => 55, 'stamina' => 70, 'defense' => 34, 'attack' => 22,
                    'shot' => 19, 'pass' => 22, 'dribble' => 19,
                    'block' => 27, 'intercept' => 26, 'tackle' => 27,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur central très solide, Nakayama forme avec Nishio un duo défensif difficile à contourner.'
            ],

            [
                'Kozo', 'Kawada', 12, 'Defender', 290,
                [
                    'speed' => 53, 'stamina' => 68, 'defense' => 31, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 25, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur appliqué, Kawada apporte de la densité et sécurise les côtés de la défense.'
            ],

            [
                'Toru', 'Hiraoka', 12, 'Midfielder', 280,
                [
                    'speed' => 51, 'stamina' => 66, 'defense' => 25, 'attack' => 23,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu travailleur, Hiraoka assure la transition entre défense et attaque de manière simple.'
            ],

            [
                'Takeshi', 'Kishida', 12, 'Midfielder', 350,
                [
                    'speed' => 55, 'stamina' => 70, 'defense' => 26, 'attack' => 26,
                    'shot' => 22, 'pass' => 25, 'dribble' => 23,
                    'block' => 20, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain complet, Kishida participe à la récupération et à la création, apportant de l’équilibre au collectif.',
                ['Defender'],
            ],

            [
                'Hanji', 'Urabe', 12, 'Midfielder', 400,
                [
                    'speed' => 60, 'stamina' => 75, 'defense' => 30, 'attack' => 30,
                    'shot' => 24, 'pass' => 25, 'dribble' => 25,
                    'block' => 22, 'intercept' => 25, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Leader du milieu d’Otomo, Urabe est un joueur combatif et complet. Il impose le rythme et n’hésite pas à provoquer physiquement.'
                , ['Defender']
            ],

            [
                'Shingo', 'Tadami', 12, 'Midfielder', 290,
                [
                    'speed' => 53, 'stamina' => 68, 'defense' => 25, 'attack' => 25,
                    'shot' => 23, 'pass' => 24, 'dribble' => 22,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu offensif secondaire, Tadami soutient les phases de projection et cherche à alimenter l’attaque.'
            ],

            [
                'Akio', 'Nakao', 12, 'Forward', 300,
                [
                    'speed' => 55, 'stamina' => 70, 'defense' => 18, 'attack' => 31,
                    'shot' => 26, 'pass' => 21, 'dribble' => 23,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant travailleur, Nakao joue dos au but et crée des espaces pour son partenaire d’attaque.'
            ],

            [
                'Shun', 'Nitta', 12, 'Forward', 475,
                [
                    'speed' => 70, 'stamina' => 75, 'defense' => 18, 'attack' => 39,
                    'shot' => 29, 'pass' => 23, 'dribble' => 26,
                    'block' => 18, 'intercept' => 22, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Star offensive d’Otomo, Nitta est un attaquant explosif et opportuniste. Sa vitesse et son sens du but font de lui la principale menace de l’équipe.'
            ],
        ];
    }

    /**
     * Remplaçants de Otomo : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
            ['Shunsuke', 'Wada', 12, 'Defender', 165, [
                'speed' => 46, 'stamina' => 63, 'defense' => 26, 'attack' => 18,
                'shot' => 16, 'pass' => 17, 'dribble' => 15, 'block' => 22,
                'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant d\'Otomo.'],
            ['Yuya', 'Saeki', 12, 'Midfielder', 170, [
                'speed' => 48, 'stamina' => 64, 'defense' => 22, 'attack' => 21,
                'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 18,
                'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant d\'Otomo.'],
            ['Kazuki', 'Hara', 12, 'Forward', 175, [
                'speed' => 54, 'stamina' => 62, 'defense' => 17, 'attack' => 24,
                'shot' => 23, 'pass' => 19, 'dribble' => 21, 'block' => 15,
                'intercept' => 15, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant d\'Otomo.'],
        ];
    }

    /**
     * Coups spéciaux des joueurs de Otomo, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [
            'shun-nitta' => [[
                'key'         => 'nitta_falcon_volley',
                'mode'        => 'attack',
                'label'       => 'Volée du Faucon',
                'short_label' => 'Faucon',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Une volée en mouvement après un appel en profondeur, digne d’un faucon.',
            ]],

            'hanji-urabe' => [[
                'key'         => 'urabe_rough_press',
                'mode'        => 'defense',
                'label'       => 'Pressing agressif',
                'short_label' => 'Pressing',
                'cooldown'    => 2,
                'base_action' => 'tackle',
                'description' => 'Urabe harcèle physiquement le porteur de balle jusqu’à lui prendre le ballon.',
            ]],
        ];
    }
}

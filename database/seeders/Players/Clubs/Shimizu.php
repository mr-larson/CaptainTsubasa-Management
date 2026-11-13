<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Shimizu (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Shimizu
{
    /**
     * Titulaires de Shimizu, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
            [
                'Morimichi', 'Kawakami', 12, 'Goalkeeper', 300,
                [
                    'speed' => 40, 'stamina' => 65, 'defense' => 26, 'attack' => 16,
                    'shot' => 15, 'pass' => 17, 'dribble' => 15,
                    'block' => 21, 'intercept' => 20, 'tackle' => 17,
                    'hand_save' => 24, 'punch_save' => 22
                ],
                'Gardien de Shimizu, Kawakami est un portier calme et discipliné. Peu mobile, il compense par son placement et sa lecture du jeu.'
            ],

            [
                'Takeshi', 'Kudo', 12, 'Defender', 240,
                [
                    'speed' => 45, 'stamina' => 55, 'defense' => 24, 'attack' => 20,
                    'shot' => 17, 'pass' => 18, 'dribble' => 16,
                    'block' => 21, 'intercept' => 19, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur appliqué, Kudo joue simple et privilégie la protection de sa surface.'
            ],

            [
                'Ichiro', 'Kanda', 12, 'Defender', 235,
                [
                    'speed' => 43, 'stamina' => 58, 'defense' => 25, 'attack' => 19,
                    'shot' => 17, 'pass' => 18, 'dribble' => 16,
                    'block' => 22, 'intercept' => 20, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur rigoureux, Kanda renforce l’axe et ferme les espaces sans prise de risque.'
            ],

            [
                'Yuto', 'Ibaraki', 12, 'Defender', 230,
                [
                    'speed' => 44, 'stamina' => 57, 'defense' => 24, 'attack' => 18,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 21, 'intercept' => 20, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur latéral discret, Ibaraki sécurise son couloir et soutient le bloc défensif.'
            ],

            [
                'Hiroshi', 'Suzuki', 12, 'Defender', 225,
                [
                    'speed' => 42, 'stamina' => 56, 'defense' => 24, 'attack' => 17,
                    'shot' => 15, 'pass' => 16, 'dribble' => 15,
                    'block' => 21, 'intercept' => 19, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur de complément, Suzuki joue bas et applique strictement les consignes défensives.'
            ],

            [
                'Daisuke', 'Takada', 12, 'Midfielder', 245,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 22, 'attack' => 22,
                    'shot' => 21, 'pass' => 22, 'dribble' => 21,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu central, Takada assure la transition et tente de poser le jeu dans un rythme mesuré.'
            ],

            [
                'Ryota', 'Nakao', 12, 'Midfielder', 240,
                [
                    'speed' => 48, 'stamina' => 59, 'defense' => 21, 'attack' => 21,
                    'shot' => 20, 'pass' => 21, 'dribble' => 20,
                    'block' => 19, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu travailleur, Nakao participe au pressing léger et soutient la conservation du ballon.'
            ],

            [
                'Shinji', 'Iimura', 12, 'Midfielder', 235,
                [
                    'speed' => 46, 'stamina' => 57, 'defense' => 21, 'attack' => 23,
                    'shot' => 22, 'pass' => 22, 'dribble' => 21,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu offensif discret, Iimura tente d’apporter un minimum de créativité dans un jeu très prudent.'
            ],

            [
                'Koji', 'Murakami', 12, 'Midfielder', 230,
                [
                    'speed' => 45, 'stamina' => 56, 'defense' => 21, 'attack' => 21,
                    'shot' => 20, 'pass' => 21, 'dribble' => 20,
                    'block' => 19, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Murakami renforce la densité au centre et limite les pertes de balle.'
            ],

            [
                'Kazumasa', 'Kato', 12, 'Forward', 250,
                [
                    'speed' => 49, 'stamina' => 60, 'defense' => 20, 'attack' => 26,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant principal de Shimizu, Kato cherche à exploiter les rares occasions créées par son équipe.'
            ],

            [
                'Takashi', 'Obayashi', 12, 'Forward', 240,
                [
                    'speed' => 48, 'stamina' => 58, 'defense' => 19, 'attack' => 24,
                    'shot' => 23, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de soutien, Obayashi accompagne Kato et tente de peser sur la défense adverse par son activité.'
            ],
        ];
    }

    /**
     * Remplaçants de Shimizu : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
            ['Naoto', 'Kishi', 12, 'Defender', 155, [
                'speed' => 40, 'stamina' => 56, 'defense' => 23, 'attack' => 17,
                'shot' => 15, 'pass' => 16, 'dribble' => 15, 'block' => 20,
                'intercept' => 18, 'tackle' => 20, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Shimizu.'],
            ['Yosuke', 'Murata', 12, 'Midfielder', 160, [
                'speed' => 42, 'stamina' => 57, 'defense' => 20, 'attack' => 19,
                'shot' => 18, 'pass' => 20, 'dribble' => 18, 'block' => 17,
                'intercept' => 17, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Shimizu.'],
            ['Kazuya', 'Nishida', 12, 'Forward', 165, [
                'speed' => 46, 'stamina' => 58, 'defense' => 15, 'attack' => 22,
                'shot' => 21, 'pass' => 17, 'dribble' => 19, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Shimizu.'],
        ];
    }

    /**
     * Coups spéciaux des joueurs de Shimizu, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [];
    }
}

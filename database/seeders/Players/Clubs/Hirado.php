<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Hirado (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Hirado
{
    /**
     * Titulaires de Hirado, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
            [
                'Akira', 'Hatakeyama', 12, 'Goalkeeper', 225,
                [
                    'speed' => 55, 'stamina' => 65, 'defense' => 30, 'attack' => 16,
                    'shot' => 15, 'pass' => 18, 'dribble' => 15,
                    'block' => 21, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 25, 'punch_save' => 22
                ],
                'Gardien de Hirado, Hatakeyama est un portier sérieux et discipliné. Il s’appuie sur une défense très physique menée par Jito pour tenir face aux attaques adverses.'
            ],

            [
                'Kazuaki', 'Soda', 12, 'Defender', 235,
                [
                    'speed' => 60, 'stamina' => 67, 'defense' => 28, 'attack' => 22,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur engagé de Hirado, Soda joue un football rude et direct. Il applique un marquage serré et participe au pressing agressif de l’équipe.'
            ],

            [
                'Toshio', 'Akizawa', 12, 'Defender', 240,
                [
                    'speed' => 58, 'stamina' => 68, 'defense' => 29, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur solide, Akizawa complète la ligne arrière en assurant les couvertures et en renforçant le marquage autour de Jito.'
            ],

            [
                'Hiroshi', 'Jito', 13, 'Defender', 440,
                [
                    'speed' => 65, 'stamina' => 85, 'defense' => 36, 'attack' => 26,
                    'shot' => 22, 'pass' => 24, 'dribble' => 23,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Capitaine et pilier de Hirado, Jito est un défenseur puissant et intimidant. Redoutable dans les duels, il impose sa présence physique et coupe les attaques par la force et l’anticipation.'
            ],

            [
                'Shinji', 'Noda', 12, 'Defender', 230,
                [
                    'speed' => 57, 'stamina' => 66, 'defense' => 26, 'attack' => 19,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 21, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur travailleur, Noda soutient la récupération et relance de manière simple. Il privilégie le collectif et l’équilibre.'
            ],

            [
                'Tsutomu', 'Nagaoka', 12, 'Defender', 225,
                [
                    'speed' => 56, 'stamina' => 65, 'defense' => 26, 'attack' => 21,
                    'shot' => 12, 'pass' => 22, 'dribble' => 19,
                    'block' => 24, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur, Nagaoka assure la continuité du jeu et renforce le bloc compact de Hirado.'
            ],

            [
                'Koji', 'Nakajo', 12, 'Midfielder', 220,
                [
                    'speed' => 55, 'stamina' => 64, 'defense' => 25, 'attack' => 21,
                    'shot' => 20, 'pass' => 22, 'dribble' => 20,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu discipliné, Nakajo joue simple et participe à la pression collective sans chercher la prise de risque.'
            ],

            [
                'Shinji', 'Morisue', 12, 'Midfielder', 235,
                [
                    'speed' => 59, 'stamina' => 67, 'defense' => 27, 'attack' => 22,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu polyvalent, Morisue aide à maintenir la densité au centre et soutient les transitions rapides.'
            ],

            [
                'Kazuo', 'Takeno', 12, 'Midfielder', 240,
                [
                    'speed' => 58, 'stamina' => 66, 'defense' => 27, 'attack' => 23,
                    'shot' => 22, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain actif, Takeno participe à la récupération et sert de relais pour amener le ballon vers Sano en attaque.'
            ],

            [
                'Katsumi', 'Himeji', 12, 'Forward', 230,
                [
                    'speed' => 60, 'stamina' => 68, 'defense' => 18, 'attack' => 26,
                    'shot' => 24, 'pass' => 21, 'dribble' => 22,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant de soutien, Himeji occupe la défense adverse et profite des seconds ballons dans un jeu direct.'
            ],

            [
                'Mitsuru', 'Sano', 13, 'Forward', 340,
                [
                    'speed' => 62, 'stamina' => 76, 'defense' => 20, 'attack' => 36,
                    'shot' => 28, 'pass' => 22, 'dribble' => 26,
                    'block' => 18, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant vedette de Hirado, Sano est un dribbleur rapide et technique. Il forme un duo redoutable avec Jito, profitant des récupérations musclées pour partir en contre et provoquer.',
                ['Midfielder'],
            ],
        ];
    }

    /**
     * Remplaçants de Hirado : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
            ['Makoto', 'Fukuda', 12, 'Defender', 160, [
                'speed' => 52, 'stamina' => 64, 'defense' => 25, 'attack' => 18,
                'shot' => 16, 'pass' => 17, 'dribble' => 15, 'block' => 22,
                'intercept' => 20, 'tackle' => 22, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Hirado.'],
            ['Ryo', 'Kawamoto', 12, 'Midfielder', 165, [
                'speed' => 49, 'stamina' => 63, 'defense' => 21, 'attack' => 20,
                'shot' => 19, 'pass' => 21, 'dribble' => 19, 'block' => 17,
                'intercept' => 18, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Hirado.'],
            ['Tatsuya', 'Kimura', 12, 'Forward', 170, [
                'speed' => 55, 'stamina' => 62, 'defense' => 16, 'attack' => 23,
                'shot' => 22, 'pass' => 18, 'dribble' => 20, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Hirado.'],
        ];
    }

    /**
     * Coups spéciaux des joueurs de Hirado, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [
            'hiroshi-jito' => [[
                'key'         => 'jito_mountain_tackle',
                'mode'        => 'defense',
                'label'       => 'Tacle de la montagne',
                'short_label' => 'Montagne',
                'cooldown'    => 3,
                'base_action' => 'tackle',
                'description' => 'Un tacle d’une puissance colossale qui stoppe physiquement l’attaquant.',
            ]],

            'mitsuru-sano' => [[
                'key'         => 'sano_quick_counter',
                'mode'        => 'attack',
                'label'       => 'Contre éclair',
                'short_label' => 'Contre',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Sano déclenche un contre rapide en éliminant son vis-à-vis en un geste.',
            ]],
        ];
    }
}

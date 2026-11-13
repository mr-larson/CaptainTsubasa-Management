<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Shutetsu (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Shutetsu
{
    /**
     * Titulaires de Shutetsu, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
            [
                'Genzo', 'Wakabayashi', 12, 'Goalkeeper', 500,
                [
                    'speed' => 70, 'stamina' => 70, 'defense' => 39, 'attack' => 18,
                    'shot' => 16, 'pass' => 18, 'dribble' => 16,
                    'block' => 22, 'intercept' => 24, 'tackle' => 18,
                    'hand_save' => 30, 'punch_save' => 29
                ],
                'Surnommé le « SGGK » (Super Great Goalkeeper), Wakabayashi est le gardien prodige formé en Allemagne. Pilier de Shutetsu, il inspire le respect et stabilise toute l’équipe par son autorité et ses arrêts décisifs.'
            ],

            [
                'Kenta', 'Shimada', 12, 'Defender', 250,
                [
                    'speed' => 50, 'stamina' => 60, 'defense' => 26, 'attack' => 18,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 22, 'intercept' => 20, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur appliqué de Shutetsu, Shimada joue un football simple et physique. Il se concentre sur le marquage et la protection de la zone devant Wakabayashi.'
            ],

            [
                'Shingo', 'Takasugi', 12, 'Defender', 375,
                [
                    'speed' => 64, 'stamina' => 70, 'defense' => 30, 'attack' => 20,
                    'shot' => 18, 'pass' => 20, 'dribble' => 17,
                    'block' => 25, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur fiable et rugueux de Shutetsu, Takasugi est l’un des cadres de la ligne arrière. Solide dans les duels et bon dans le timing, il sécurise l’équipe dans les matchs importants.'
            ],

            [
                'Wanatabe', 'Matsumo', 12, 'Defender', 225,
                [
                    'speed' => 48, 'stamina' => 58, 'defense' => 24, 'attack' => 17,
                    'shot' => 16, 'pass' => 16, 'dribble' => 15,
                    'block' => 21, 'intercept' => 19, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur de rotation, Matsumo se distingue surtout par sa rigueur. Il joue bas, ferme les angles et assure les couvertures sans chercher la prise de risque.'
            ],

            [
                'Kohei', 'Nakamoto', 12, 'Defender', 240,
                [
                    'speed' => 49, 'stamina' => 59, 'defense' => 25, 'attack' => 17,
                    'shot' => 16, 'pass' => 17, 'dribble' => 16,
                    'block' => 22, 'intercept' => 20, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur travailleur, Nakamoto complète la défense de Shutetsu par son engagement et son sens du placement. Il aide à conserver un bloc compact devant son gardien.'
            ],

            [
                'Yoshioka', 'Kurata', 12, 'Midfielder', 260,
                [
                    'speed' => 52, 'stamina' => 63, 'defense' => 21, 'attack' => 23,
                    'shot' => 20, 'pass' => 22, 'dribble' => 21,
                    'block' => 18, 'intercept' => 19, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain polyvalent, Kurata assure la transition défense-attaque. Il oriente le jeu de manière simple et soutient le pressing au milieu.'
            ],

            [
                'Akiyoshi', 'Osaki', 12, 'Midfielder', 270,
                [
                    'speed' => 54, 'stamina' => 65, 'defense' => 22, 'attack' => 24,
                    'shot' => 21, 'pass' => 23, 'dribble' => 22,
                    'block' => 18, 'intercept' => 19, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Osaki apporte de l’intensité et une bonne activité à Shutetsu. Il participe au jeu collectif et se projette quand l’équipe prend l’avantage.'
            ],

            [
                'Mamoru', 'Izawa', 12, 'Midfielder', 450,
                [
                    'speed' => 68, 'stamina' => 70, 'defense' => 28, 'attack' => 32,
                    'shot' => 25, 'pass' => 26, 'dribble' => 22,
                    'block' => 20, 'intercept' => 23, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Chef d’orchestre de Shutetsu au milieu, Izawa est un joueur intelligent et complet. Il sait temporiser, distribuer et servir de relais, ce qui rend l’équipe plus dangereuse à la récupération.'
            ],

            [
                'Kitajima', 'Inamura', 12, 'Midfielder', 290,
                [
                    'speed' => 55, 'stamina' => 67, 'defense' => 23, 'attack' => 25,
                    'shot' => 22, 'pass' => 24, 'dribble' => 23,
                    'block' => 19, 'intercept' => 20, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu dynamique, Inamura sert de soutien à Izawa et participe à la construction. Il propose des solutions courtes et se rend disponible entre les lignes.'
            ],

            [
                'Teppei', 'Kisugi', 13, 'Forward', 400,
                [
                    'speed' => 70, 'stamina' => 80, 'defense' => 16, 'attack' => 34,
                    'shot' => 26, 'pass' => 22, 'dribble' => 25,
                    'block' => 16, 'intercept' => 16, 'tackle' => 16,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Avant-centre de Shutetsu, Kisugi est un finisseur opportuniste. Il joue sur ses déplacements, attaque la profondeur et cherche à conclure rapidement dès qu’une brèche s’ouvre.'
            ],

            [
                'Hajime', 'Taki', 12, 'Forward', 400,
                [
                    'speed' => 80, 'stamina' => 75, 'defense' => 18, 'attack' => 33,
                    'shot' => 25, 'pass' => 22, 'dribble' => 26,
                    'block' => 16, 'intercept' => 17, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant très rapide, Taki est l’arme de Shutetsu en contre-attaque. Il mise sur la vitesse et le dribble pour déborder, provoquer et créer des situations de tir.',
                ['Midfielder'],
            ],
        ];
    }

    /**
     * Remplaçants de Shutetsu : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
            ['Hiroki', 'Fujii', 12, 'Defender', 160, [
                'speed' => 42, 'stamina' => 60, 'defense' => 24, 'attack' => 17,
                'shot' => 16, 'pass' => 16, 'dribble' => 15, 'block' => 21,
                'intercept' => 19, 'tackle' => 21, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Shutetsu.'],
            ['Daisuke', 'Mori', 12, 'Midfielder', 165, [
                'speed' => 44, 'stamina' => 62, 'defense' => 20, 'attack' => 20,
                'shot' => 19, 'pass' => 21, 'dribble' => 19, 'block' => 17,
                'intercept' => 18, 'tackle' => 17, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Shutetsu.'],
            ['Yosuke', 'Kimura', 12, 'Forward', 170, [
                'speed' => 46, 'stamina' => 60, 'defense' => 16, 'attack' => 23,
                'shot' => 22, 'pass' => 18, 'dribble' => 20, 'block' => 15,
                'intercept' => 15, 'tackle' => 15, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Shutetsu.'],
        ];
    }

    /**
     * Coups spéciaux des joueurs de Shutetsu, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [
            'genzo-wakabayashi' => [[
                'key'         => 'wakabayashi_sggk_catch',
                'mode'        => 'defense',
                'label'       => 'Arrêt SGGK',
                'short_label' => 'SGGK',
                'cooldown'    => 2,
                'base_action' => 'hand_save',
                'description' => 'Un arrêt réflexe parfait du Super Great Goalkeeper, même sur des tirs surpuissants.',
            ]],

            'shingo-takasugi' => [[
                'key'         => 'takasugi_iron_wall',
                'mode'        => 'defense',
                'label'       => 'Mur de Shutetsu',
                'short_label' => 'Mur',
                'cooldown'    => 2,
                'base_action' => 'tackle',
                'description' => 'Un tacle défensif puissant et parfaitement maîtrisé qui stoppe net l’attaquant.',
            ]],

            'mamoru-izawa' => [[
                'key'         => 'izawa_midfield_maestro',
                'mode'        => 'attack',
                'label'       => 'Maestro de Shutetsu',
                'short_label' => 'Maestro',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Une ouverture lumineuse qui met un coéquipier en position idéale.',
            ]],

            'hajime-taki' => [[
                'key'         => 'taki_speed_dribble',
                'mode'        => 'attack',
                'label'       => 'Dribble supersonique',
                'short_label' => 'Supersonique',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Taki accélère brusquement et déborde son vis-à-vis grâce à sa vitesse.',
            ]],

            'teppei-kisugi' => [[
                'key'         => 'kisugi_clinical_finish',
                'mode'        => 'attack',
                'label'       => 'Frappe clinique',
                'short_label' => 'Finisseur',
                'cooldown'    => 2,
                'base_action' => 'shot',
                'description' => 'Une frappe précise placée dans le petit filet après un appel intelligent.',
            ]],
        ];
    }
}

<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Joueurs du club Toho (univers Captain Tsubasa, collège) : titulaires,
 * remplaçants et coups spéciaux. Chaque joueur est un tableau lu par
 * PlayerSeeder : [prénom, nom, âge, poste, coût de base, stats, description,
 * postes secondaires (optionnel)]. L'ordre ici fixe les ids en base : ne pas
 * réordonner sans reseeder tout le catalogue. L'effectif est assemblé par
 * CaptainTsubasaClubPlayers, qui sert de point d'entrée.
 */
final class Toho
{
    /**
     * Titulaires de Toho, dans l'ordre de l'effectif (gardien en premier).
     *
     * @return array<int, array<int, mixed>>
     */
    public static function starters(): array
    {
        return [
            [
                'Ken', 'Wakashimazu', 13, 'Goalkeeper', 465,
                [
                    'speed' => 70, 'stamina' => 85, 'defense' => 36, 'attack' => 22,
                    'shot' => 22, 'pass' => 20, 'dribble' => 19,
                    'block' => 22, 'intercept' => 24, 'tackle' => 21,
                    'hand_save' => 28, 'punch_save' => 30
                ],
                'Gardien karatéka de Toho, Wakashimazu se distingue par ses réflexes explosifs et son style acrobatique. Capable de jouer gardien comme joueur de champ, il apporte une agressivité unique à l’équipe.'
            ],

            [
                'Kiyoshi', 'Furuta', 12, 'Defender', 250,
                [
                    'speed' => 60, 'stamina' => 68, 'defense' => 28, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 23, 'intercept' => 22, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur solide de Toho, Furuta joue un rôle strictement défensif. Il mise sur l’impact physique et le pressing pour soutenir le bloc derrière Hyuga.'
            ],

            [
                'Katsuji', 'Kawabe', 12, 'Defender', 260,
                [
                    'speed' => 62, 'stamina' => 70, 'defense' => 29, 'attack' => 22,
                    'shot' => 18, 'pass' => 20, 'dribble' => 18,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur rugueux, Kawabe incarne le style agressif de Toho. Il n’hésite pas à aller au duel pour casser le rythme adverse.'
            ],

            [
                'Tsuneo', 'Takashima', 12, 'Defender', 265,
                [
                    'speed' => 64, 'stamina' => 72, 'defense' => 30, 'attack' => 23,
                    'shot' => 19, 'pass' => 20, 'dribble' => 18,
                    'block' => 25, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Pilier de la défense de Toho, Takashima est l’un des joueurs les plus fiables derrière. Son sens du placement et sa dureté défensive sécurisent l’arrière-garde.'
            ],

            [
                'Hiroshi', 'Imai', 12, 'Defender', 255,
                [
                    'speed' => 60, 'stamina' => 66, 'defense' => 27, 'attack' => 21,
                    'shot' => 18, 'pass' => 19, 'dribble' => 17,
                    'block' => 23, 'intercept' => 22, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur discipliné, Imai complète la ligne arrière de Toho par sa régularité et son engagement constant.'
            ],

            [
                'Hideto', 'Koike', 12, 'Midfielder', 265,
                [
                    'speed' => 62, 'stamina' => 70, 'defense' => 26, 'attack' => 22,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu polyvalent, Koike assure le lien entre défense et attaque. Il joue simple et soutient l’intensité imposée par Toho.'
            ],

            [
                'Yutaka', 'Matsuki', 12, 'Midfielder', 270,
                [
                    'speed' => 64, 'stamina' => 72, 'defense' => 27, 'attack' => 24,
                    'shot' => 22, 'pass' => 24, 'dribble' => 22,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de terrain actif, Matsuki participe à la récupération et à la projection offensive. Il soutient la construction autour de Sawada.'
            ],

            [
                'Takeshi', 'Sawada', 11, 'Midfielder', 415,
                [
                    'speed' => 73, 'stamina' => 80, 'defense' => 28, 'attack' => 35,
                    'shot' => 25, 'pass' => 27, 'dribble' => 27,
                    'block' => 19, 'intercept' => 24, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Jeune stratège de Toho, Sawada est le cerveau de l’équipe. Malgré son âge, il se distingue par son intelligence de jeu et son excellente vision offensive.'
            ],

            [
                'Tadashi', 'Shimano', 12, 'Midfielder', 260,
                [
                    'speed' => 63, 'stamina' => 70, 'defense' => 26, 'attack' => 23,
                    'shot' => 21, 'pass' => 23, 'dribble' => 21,
                    'block' => 20, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu de soutien, Shimano renforce le pressing et aide à maintenir la domination physique de Toho au milieu.'
            ],

            [
                'Kojiro', 'Hyuga', 13, 'Forward', 500,
                [
                    'speed' => 78, 'stamina' => 85, 'defense' => 22, 'attack' => 42,
                    'shot' => 31, 'pass' => 20, 'dribble' => 27,
                    'block' => 18, 'intercept' => 20, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Avant-centre emblématique de Toho, Hyuga est un attaquant puissant et impitoyable. Son tir du tigre et son mental de compétiteur font de lui l’arme offensive principale de l’équipe.'
            ],

            [
                'Kazuki', 'Sorimachi', 12, 'Forward', 345,
                [
                    'speed' => 68, 'stamina' => 75, 'defense' => 20, 'attack' => 32,
                    'shot' => 26, 'pass' => 23, 'dribble' => 24,
                    'block' => 17, 'intercept' => 17, 'tackle' => 17,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Attaquant opportuniste, Sorimachi complète parfaitement Hyuga. Il profite des espaces créés par son capitaine pour se projeter et conclure.'
            ],
        ];
    }

    /**
     * Remplaçants de Toho : ils complètent l'effectif jusqu'à 14 joueurs.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function substitutes(): array
    {
        return [
            ['Ryuji', 'Endo', 12, 'Defender', 165, [
                'speed' => 55, 'stamina' => 65, 'defense' => 26, 'attack' => 19,
                'shot' => 17, 'pass' => 18, 'dribble' => 16, 'block' => 22,
                'intercept' => 20, 'tackle' => 23, 'hand_save' => 15, 'punch_save' => 15
            ], 'Défenseur remplaçant de Toho.'],
            ['Masashi', 'Goto', 12, 'Midfielder', 170, [
                'speed' => 56, 'stamina' => 63, 'defense' => 22, 'attack' => 21,
                'shot' => 20, 'pass' => 22, 'dribble' => 20, 'block' => 18,
                'intercept' => 19, 'tackle' => 18, 'hand_save' => 15, 'punch_save' => 15
            ], 'Milieu remplaçant de Toho.'],
            ['Keita', 'Ogawa', 12, 'Forward', 175, [
                'speed' => 60, 'stamina' => 62, 'defense' => 17, 'attack' => 25,
                'shot' => 23, 'pass' => 19, 'dribble' => 21, 'block' => 15,
                'intercept' => 15, 'tackle' => 16, 'hand_save' => 15, 'punch_save' => 15
            ], 'Attaquant remplaçant de Toho.'],
        ];
    }

    /**
     * Coups spéciaux des joueurs de Toho, indexés par slug « prenom-nom »
     * (vide si le club n'en a pas).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [
            'kojiro-hyuga' => [[
                'key'         => 'hyuga_tir_du_tigre',
                'mode'        => 'attack',
                'label'       => 'Tir du Tigre',
                'short_label' => 'Tigre',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Un tir ultra puissant frappé de plein fouet, qui vise à traverser le gardien.',
            ]],

            'ken-wakashimazu' => [[
                'key'         => 'wakashimazu_kungfu_save',
                'mode'        => 'defense',
                'label'       => 'Arrêt karatéka',
                'short_label' => 'Karaté',
                'cooldown'    => 2,
                'base_action' => 'punch_save',
                'description' => 'Un arrêt acrobatique utilisant des réflexes de karaté pour repousser le ballon.',
            ]],

            'takeshi-sawada' => [[
                'key'         => 'sawada_tohos_brain',
                'mode'        => 'attack',
                'label'       => 'Cerveau de Toho',
                'short_label' => 'Tacticien',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Une passe stratégique qui lance une action dangereuse autour de Hyuga.',
            ]],

            'kazuki-sorimachi' => [[
                'key'         => 'sorimachi_poacher_finish',
                'mode'        => 'attack',
                'label'       => 'Renard de surface',
                'short_label' => 'Renard',
                'cooldown'    => 2,
                'base_action' => 'shot',
                'description' => 'Sorimachi profite d’un second ballon pour frapper instantanément dans la surface.',
            ]],
        ];
    }
}

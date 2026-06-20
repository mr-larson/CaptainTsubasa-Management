<?php

namespace Database\Seeders\Players;

/**
 * Données joueurs (et special moves) — groupe : CaptainTsubasaFreePlayers.
 * Extrait de PlayerSeeder pour alléger et organiser par source.
 */
class CaptainTsubasaFreePlayers
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            // Real Seven – Not contract
            [
                'Michel', 'Yamada', 13, 'Goalkeeper', 300,
                [
                    'speed' => 60, 'stamina' => 70, 'defense' => 32, 'attack' => 18,
                    'shot' => 16, 'pass' => 19, 'dribble' => 17,
                    'block' => 22, 'intercept' => 23, 'tackle' => 18,
                    'hand_save' => 26, 'punch_save' => 24
                ],
                'Gardien de Real Seven, Yamada est un portier athlétique et fiable. Plus mobile que la moyenne, il combine réflexes et lecture du jeu.'
            ],

            [
                'Yuji', 'Sakaki', 13, 'Defender', 285,
                [
                    'speed' => 58, 'stamina' => 67, 'defense' => 30, 'attack' => 22,
                    'shot' => 18, 'pass' => 20, 'dribble' => 18,
                    'block' => 24, 'intercept' => 23, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Défenseur solide, Sakaki se distingue par son sens du placement et sa régularité dans les duels.'
            ],

            [
                'Takashi', 'Sugimoto', 13, 'Midfielder', 305,
                [
                    'speed' => 65, 'stamina' => 70, 'defense' => 30, 'attack' => 27,
                    'shot' => 25, 'pass' => 26, 'dribble' => 25,
                    'block' => 22, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu complet, Sugimoto est un relais essentiel entre la défense et l’attaque de Real Seven.'
            ],

            [
                'Nobuyuki', 'Yumikura', 13, 'Midfielder', 310,
                [
                    'speed' => 66, 'stamina' => 71, 'defense' => 31, 'attack' => 28,
                    'shot' => 26, 'pass' => 27, 'dribble' => 26,
                    'block' => 22, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu leader, Yumikura impose le tempo et apporte une grande constance dans le jeu.'
            ],

            [
                'Toshiya', 'Okano', 13, 'Midfielder', 300,
                [
                    'speed' => 64, 'stamina' => 69, 'defense' => 29, 'attack' => 27,
                    'shot' => 25, 'pass' => 26, 'dribble' => 25,
                    'block' => 22, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Milieu offensif régulier, Okano soutient les attaques et se projette intelligemment.'
            ],

            [
                'Ryoma', 'Hino', 12, 'Forward', 445,
                [
                    'speed' => 70, 'stamina' => 80, 'defense' => 20, 'attack' => 38,
                    'shot' => 30, 'pass' => 20, 'dribble' => 27,
                    'block' => 18, 'intercept' => 18, 'tackle' => 18,
                    'hand_save' => 15, 'punch_save' => 15
                ],
                'Star offensive de Real Seven, Hino est un buteur puissant et agressif. Son tir dévastateur et son mental font de lui une menace constante.'
            ],

        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public static function specialMoves(): array
    {
        return [
            // REAL SEVEN
            // =======================

            'ryoma-hino' => [[
                'key'         => 'hino_fire_shot',
                'mode'        => 'attack',
                'label'       => 'Tir de Feu',
                'short_label' => 'Feu',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Un tir surpuissant et brûlant qui semble enflammer le ballon.',
            ]],

            // =======================
            // INTERNATIONAUX — REAL SEVEN
            // =======================

            'michel-yamada' => [[
                'key'         => 'yamada_reflex_save',
                'mode'        => 'defense',
                'label'       => 'Arrêt réflexe',
                'short_label' => 'Réflexe',
                'cooldown'    => 2,
                'base_action' => 'hand_save',
                'description' => 'Gardien athlétique et mobile, il combine réflexes explosifs et bonne lecture du jeu.',
            ]],

            'shinnosuke-kazami' => [[
                'key'         => 'kazami_quick_strike',
                'mode'        => 'attack',
                'label'       => 'Frappe éclair',
                'short_label' => 'Éclair',
                'cooldown'    => 2,
                'base_action' => 'shot',
                'description' => 'Attaquant rapide et opportuniste, il multiplie les appels et conclut vite les actions.',
            ]],

            // =======================
        ];
    }
}

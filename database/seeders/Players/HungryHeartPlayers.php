<?php

namespace Database\Seeders\Players;

/**
 * Données joueurs (et special moves) — groupe : HungryHeartPlayers.
 * Extrait de PlayerSeeder pour alléger et organiser par source.
 */
class HungryHeartPlayers
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            //-------------------------------
            // Hungry Heart – Not contract
            //-------------------------------
            [
                'Kyosuke', 'Kano', 13, 'Forward', 380,
                [
                    'speed' => 68, 'stamina' => 68, 'defense' => 16, 'attack' => 30,
                    'shot' => 24, 'pass' => 20, 'dribble' => 24,
                    'block' => 14, 'intercept' => 15, 'tackle' => 14,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant fougueux de Jyoyo, surnommé Orangehead. Formé par son frère Seisuke, il retrouve sa passion du football au contact de ses coéquipiers et s\'impose comme l\'attaquant vedette de l\'équipe.'
            ],

            [
                'Rafael', 'Del Franco', 13, 'Midfielder', 355,
                [
                    'speed' => 64, 'stamina' => 66, 'defense' => 20, 'attack' => 28,
                    'shot' => 20, 'pass' => 26, 'dribble' => 23,
                    'block' => 16, 'intercept' => 19, 'tackle' => 17,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Meneur de jeu brésilien de Jyoyo. D\'abord individualiste et motivé par l\'argent pour aider sa famille, il évolue au contact de Kyosuke et devient le cerveau de son équipe.'
            ],

            [
                'Koji', 'Sakai', 13, 'Goalkeeper', 330,
                [
                    'speed' => 52, 'stamina' => 64, 'defense' => 22, 'attack' => 12,
                    'shot' => 10, 'pass' => 14, 'dribble' => 12,
                    'block' => 20, 'intercept' => 18, 'tackle' => 15,
                    'hand_save' => 26, 'punch_save' => 24,
                ],
                'Gardien mi-japonais mi-suédén de Jyoyo. Apprécié des filles, il surmonte sa peur des blessures grâce à ses coéquipiers et s\'affirme progressivement comme un gardien fiable.'
            ],

            [
                'Gohzo', 'Kamata', 13, 'Defender', 305,
                [
                    'speed' => 56, 'stamina' => 66, 'defense' => 25, 'attack' => 16,
                    'shot' => 13, 'pass' => 15, 'dribble' => 14,
                    'block' => 23, 'intercept' => 20, 'tackle' => 23,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Défenseur et vice-capitaine de Jyoyo, ancien avant reconverti. Leader défensif sérieux et respecté de ses coéquipiers.'
            ],

            [
                'Yoshiya', 'Sako', 13, 'Midfielder', 295,
                [
                    'speed' => 58, 'stamina' => 62, 'defense' => 22, 'attack' => 23,
                    'shot' => 17, 'pass' => 23, 'dribble' => 19,
                    'block' => 16, 'intercept' => 19, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Capitaine et meneur de jeu de Jyoyo avant l\'arrivée de Rodrigo. Posé et intelligent, il aide toujours ses coéquipiers et reste un joueur fiable.'
            ],

            [
                'Hiroshi', 'Ichikawa', 13, 'Midfielder', 280,
                [
                    'speed' => 60, 'stamina' => 60, 'defense' => 20, 'attack' => 22,
                    'shot' => 17, 'pass' => 20, 'dribble' => 19,
                    'block' => 16, 'intercept' => 18, 'tackle' => 17,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu fougueux de Jyoyo. Il croit profondément au jeu collectif et s\'oppose d\'abord à l\'individualisme de Rodrigo avant de s\'imposer comme vice-capitaine.'
            ],

            [
                'Masashi', 'Esaka', 13, 'Midfielder', 270,
                [
                    'speed' => 56, 'stamina' => 62, 'defense' => 19, 'attack' => 21,
                    'shot' => 16, 'pass' => 20, 'dribble' => 18,
                    'block' => 15, 'intercept' => 17, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu jovial de Jyoyo, passionné par le bien-être de l\'équipe. Sako et Kamata le choisissent comme nouveau capitaine pour son attitude exemplaire.'
            ],

        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public static function specialMoves(): array
    {
        return [
            // HUNGRY HEART
            // =======================

            'kyosuke-kano' => [[
                'key'         => 'kano_orangehead_strike',
                'mode'        => 'attack',
                'label'       => 'Frappe Orangehead',
                'short_label' => 'Orangehead',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'L’attaquant fougueux libère toute sa fureur dans une frappe puissante et rageuse.',
            ]],

            'rafael-del-franco' => [[
                'key'         => 'delfranco_samba_playmaker',
                'mode'        => 'attack',
                'label'       => 'Meneur samba',
                'short_label' => 'Samba',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Meneur brésilien au cerveau de jeu, il oriente l’attaque avec créativité.',
            ]],

            'koji-sakai' => [[
                'key'         => 'sakai_brave_save',
                'mode'        => 'defense',
                'label'       => 'Arrêt courageux',
                'short_label' => 'Courage',
                'cooldown'    => 2,
                'base_action' => 'hand_save',
                'description' => 'Il surmonte sa peur des blessures pour se détendre et repousser le tir.',
            ]],

            // =======================
        ];
    }

    /**
     * Exceptions de nationalité (slug "prenom-nom" → pays). Le reste de
     * l'effectif est japonais par défaut (cf. PlayerSeeder).
     * @return array<string, string>
     */
    public static function nationalities(): array
    {
        return [
            'rafael-del-franco' => 'Brésil',
            'koji-sakai'        => 'Suède',
        ];
    }
}

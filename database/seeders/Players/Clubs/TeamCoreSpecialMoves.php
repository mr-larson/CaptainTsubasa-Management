<?php

namespace Database\Seeders\Players\Clubs;

/**
 * Coups spéciaux complémentaires des cadres d'équipe de l'univers Captain
 * Tsubasa (joueurs clés dont le club n'a pas de classe dédiée de coups
 * spéciaux). Indexés par slug « prenom-nom » ; fusionnés par
 * CaptainTsubasaClubPlayers::specialMoves().
 */
final class TeamCoreSpecialMoves
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return [
            'yuzo-morisaki' => [[
                'key'         => 'morisaki_desperate_dive',
                'mode'        => 'defense',
                'label'       => 'Plongeon désespéré',
                'short_label' => 'Plongeon',
                'cooldown'    => 2,
                'base_action' => 'hand_save',
                'description' => 'Gardien titulaire de Nankatsu, Morisaki compense ses limites par un plongeon désespéré pour repousser le tir.',
            ]],

            'tsuneo-takashima' => [[
                'key'         => 'takashima_toho_rock',
                'mode'        => 'defense',
                'label'       => 'Roc de Toho',
                'short_label' => 'Roc',
                'cooldown'    => 2,
                'base_action' => 'tackle',
                'description' => 'Pilier de la défense de Toho, il sécurise l’arrière-garde par son placement et un tacle dur au bon moment.',
            ]],

            'kazumasa-oda' => [[
                'key'         => 'kazumasa_oda_finisher',
                'mode'        => 'attack',
                'label'       => 'Frappe du finisseur',
                'short_label' => 'Finisseur',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Avant-centre principal de Furano, Oda profite du pressing de son équipe pour conclure d’une frappe puissante.',
            ]],

            'minoru-honma' => [[
                'key'         => 'honma_box_fox',
                'mode'        => 'attack',
                'label'       => 'Renard de Musashi',
                'short_label' => 'Renard',
                'cooldown'    => 2,
                'base_action' => 'shot',
                'description' => 'Attaquant principal de Musashi, Honma exploite les occasions créées par Misugi pour frapper dans la surface.',
            ]],

            'yuichiro-daimaru' => [[
                'key'         => 'daimaru_hanawa_brain',
                'mode'        => 'attack',
                'label'       => 'Cerveau de Hanawa',
                'short_label' => 'Cerveau',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Cerveau de Hanawa, Daimaru oriente le jeu et délivre la passe qui lance l’attaque.',
            ]],

            'koji-nishio' => [[
                'key'         => 'nishio_otomo_stopper',
                'mode'        => 'defense',
                'label'       => 'Stoppeur d’Otomo',
                'short_label' => 'Stoppeur',
                'cooldown'    => 2,
                'base_action' => 'block',
                'description' => 'Pilier défensif d’Otomo, Nishio est un stoppeur puissant et constant qui ferme l’axe.',
            ]],

            'yuichi-suenaga' => [[
                'key'         => 'suenaga_meiwa_blade',
                'mode'        => 'attack',
                'label'       => 'Lame de Meiwa',
                'short_label' => 'Lame',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Avant-centre principal de Meiwa, Suenaga est un finisseur tranchant qui profite du jeu collectif pour marquer.',
            ]],

            'shinishi-sakamoto' => [[
                'key'         => 'sakamoto_meiwa_engine',
                'mode'        => 'attack',
                'label'       => 'Moteur de Meiwa',
                'short_label' => 'Moteur',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Moteur du jeu de Meiwa, Sakamoto récupère, oriente et relance les offensives de son équipe.',
            ]],

            'motoharu-nagano' => [[
                'key'         => 'nagano_meiwa_rampart',
                'mode'        => 'defense',
                'label'       => 'Rempart de Meiwa',
                'short_label' => 'Rempart',
                'cooldown'    => 2,
                'base_action' => 'tackle',
                'description' => 'Pilier défensif de Meiwa, Nagano est un stoppeur puissant et constant qui muselle les attaquants adverses.',
            ]],

            'tetsuo-ishida' => [[
                'key'         => 'ishida_minawi_flash',
                'mode'        => 'attack',
                'label'       => 'Éclair de Minawi',
                'short_label' => 'Éclair',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Joueur clé de Minawi, Ishida est un milieu offensif explosif et technique qui perce les défenses balle au pied.',
            ]],

            'kazumasa-kato' => [[
                'key'         => 'kazumasa_kato_poacher',
                'mode'        => 'attack',
                'label'       => 'Pointe de Shimizu',
                'short_label' => 'Pointe',
                'cooldown'    => 2,
                'base_action' => 'shot',
                'description' => 'Attaquant principal de Shimizu, Kato guette la moindre occasion pour la convertir d’une frappe opportuniste.',
            ]],

            'junichi-nagasaki' => [[
                'key'         => 'nagasaki_shimada_striker',
                'mode'        => 'attack',
                'label'       => 'Buteur de Shimada',
                'short_label' => 'Buteur',
                'cooldown'    => 2,
                'base_action' => 'shot',
                'description' => 'Fer de lance de Shimada, Nagasaki tente de convertir les rares occasions de son équipe en buts.',
            ]],
        ];
    }
}

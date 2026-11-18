<?php

namespace Database\Seeders\Players\CaptainTsubasa\International;

/** Techniques transposées aux actions déjà prises en charge par le moteur. */
final class WorldYouthMoves
{
    public static function all(): array
    {
        return [
            'zangiev' => [[
                'key' => 'zangiev_great_rock_blaster', 'mode' => 'attack', 'label' => 'Great Rock Blaster',
                'short_label' => 'Rock', 'cooldown' => 3, 'base_action' => 'shot',
                'description' => 'La frappe puissante de Zangiev, présentée sur sa fiche officielle World Fighters.',
            ]],
            'junguang-xiao' => [[
                'key' => 'xiao_kickback_cannon', 'mode' => 'defense', 'label' => 'Canon de renvoi',
                'short_label' => 'Renvoi', 'cooldown' => 3, 'base_action' => 'block',
                'description' => 'Lecture de la trajectoire et blocage technique inspirés du renvoi de Xiao.',
            ]],
            'mark-owairan' => [[
                'key' => 'owairan_speed_interception', 'mode' => 'defense', 'label' => 'Interception éclair',
                'short_label' => 'Éclair', 'cooldown' => 2, 'base_action' => 'intercept',
                'description' => 'Le libéro anticipe la passe et surgit pour couper la trajectoire.',
            ]],
            'vulcan' => [[
                'key' => 'vulcan_giant_header', 'mode' => 'attack', 'label' => 'Tête du géant',
                'short_label' => 'Géant', 'cooldown' => 3, 'base_action' => 'shot',
                'description' => 'Une finition puissante inspirée de la domination aérienne de Vulcan.',
            ]],
            'xiang-fei' => [[
                'key' => 'fei_mysterious_bird_header', 'mode' => 'attack', 'label' => 'Tête de l’Oiseau mystérieux',
                'short_label' => 'Oiseau', 'cooldown' => 3, 'base_action' => 'shot',
                'description' => 'Fei exploite son allonge pour conclure les ballons servis dans la surface.',
            ]],
            'zhongming-wang' => [[
                'key' => 'wang_exquisite_cross', 'mode' => 'attack', 'label' => 'Centre de toute beauté',
                'short_label' => 'Précision', 'cooldown' => 2, 'base_action' => 'cross',
                'description' => 'Un centre précis pour préparer la finition aérienne de son partenaire.',
            ]],
            'yong-un-lee' => [[
                'key' => 'lee_skill_dribble', 'mode' => 'attack', 'label' => 'Dribble technique',
                'short_label' => 'Technique', 'cooldown' => 2, 'base_action' => 'dribble',
                'description' => 'Une conduite souple et une feinte rapide pour ouvrir un passage.',
            ]],
            'in-cheon-cha' => [[
                'key' => 'cha_tank_shot', 'mode' => 'attack', 'label' => 'Tir du tank',
                'short_label' => 'Tank', 'cooldown' => 3, 'base_action' => 'shot',
                'description' => 'Cha concentre sa puissance physique dans une frappe lourde.',
            ]],
        ];
    }
}

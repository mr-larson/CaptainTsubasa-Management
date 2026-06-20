<?php

namespace Database\Seeders\Players;

/**
 * Données joueurs (et special moves) — groupe : BlueLockPlayers.
 * Extrait de PlayerSeeder pour alléger et organiser par source.
 */
class BlueLockPlayers
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            //-------------------------------
            // Blue Lock – Not contract
            //-------------------------------
            [
                'Yoichi', 'Isagi', 13, 'Midfielder', 400,
                [
                    'speed' => 68, 'stamina' => 70, 'defense' => 18, 'attack' => 32,
                    'shot' => 26, 'pass' => 26, 'dribble' => 24,
                    'block' => 15, 'intercept' => 18, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Isagi brille par sa lecture du jeu et sa capacité à analyser le terrain pour être toujours au bon endroit au bon moment.',
                ['Forward']
            ],

            [
                'Meguru', 'Bachira', 13, 'Midfielder', 385,
                [
                    'speed' => 70, 'stamina' => 68, 'defense' => 18, 'attack' => 30,
                    'shot' => 22, 'pass' => 24, 'dribble' => 30,
                    'block' => 15, 'intercept' => 18, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu offensif instinctif, Bachira joue en suivant son instinct et ses dribbles imprévisibles créent le chaos dans les défenses adverses.'
            ],

            [
                'Hyoma', 'Chigiri', 13, 'Midfielder', 390,
                [
                    'speed' => 84, 'stamina' => 64, 'defense' => 16, 'attack' => 30,
                    'shot' => 24, 'pass' => 20, 'dribble' => 24,
                    'block' => 13, 'intercept' => 16, 'tackle' => 14,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Ailier prodige doté d\'une vitesse à couper le souffle. Fragilisé par une blessure au genou, il se surpasse malgré tout pour devenir un joueur exceptionnel.',
                ['Forward', 'Defender']
            ],

            [
                'Rensuke', 'Kunigami', 13, 'Forward', 388,
                [
                    'speed' => 66, 'stamina' => 72, 'defense' => 20, 'attack' => 32,
                    'shot' => 28, 'pass' => 19, 'dribble' => 20,
                    'block' => 16, 'intercept' => 17, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant puissant spécialiste des frappes longues. Son tir gauche dévastateur de 27 mètres est sa marque de fabrique. Il rêve de devenir un super-héros du football.'
            ],

            [
                'Seishiro', 'Nagi', 13, 'Forward', 415,
                [
                    'speed' => 68, 'stamina' => 64, 'defense' => 18, 'attack' => 36,
                    'shot' => 28, 'pass' => 24, 'dribble' => 28,
                    'block' => 15, 'intercept' => 17, 'tackle' => 15,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Génie naturel du football découvert par Reo. Ses amortis et son contrôle de balle exceptionnels font de lui un joueur unique, capable de dominer sans effort apparent.',
                ['Midfielder']
            ],

            [
                'Reo', 'Mikage', 13, 'Defender', 378,
                [
                    'speed' => 64, 'stamina' => 70, 'defense' => 22, 'attack' => 28,
                    'shot' => 20, 'pass' => 28, 'dribble' => 23,
                    'block' => 17, 'intercept' => 20, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Héritier de la Mikage Corporation. Polyvalent et intelligent, il a tout pour réussir et son seul désir est de gagner la Coupe du monde avec Nagi.'
            ],

            [
                'Shoei', 'Baro', 13, 'Forward', 420,
                [
                    'speed' => 70, 'stamina' => 72, 'defense' => 20, 'attack' => 36,
                    'shot' => 30, 'pass' => 16, 'dribble' => 26,
                    'block' => 16, 'intercept' => 17, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Surnommé le Roi du terrain. Avant-centre égoïste et dominant, Baro cherche systématiquement le but par la puissance physique et l\'endurance.'
            ],

            [
                'Rin', 'Itoshi', 13, 'Forward', 435,
                [
                    'speed' => 72, 'stamina' => 74, 'defense' => 22, 'attack' => 38,
                    'shot' => 30, 'pass' => 26, 'dribble' => 28,
                    'block' => 17, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Frère cadet de Sae Itoshi, déterminé à le surpasser. Attaquant complet capable d\'atteindre un état de flow qui le propulse à un niveau supérieur lors des grands matchs.',
                ['Midfielder']
            ],

            [
                'Ryusei', 'Shido', 13, 'Forward', 340,
                [
                    'speed' => 64, 'stamina' => 62, 'defense' => 16, 'attack' => 26,
                    'shot' => 21, 'pass' => 20, 'dribble' => 21,
                    'block' => 13, 'intercept' => 14, 'tackle' => 14,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant de Matsukaze Kokuo invité au Blue Lock. Il sympathise avec Isagi mais est éliminé dès la première épreuve par ce dernier.'
            ],

            [
                'Jingo', 'Raichi', 13, 'Forward', 295,
                [
                    'speed' => 60, 'stamina' => 64, 'defense' => 18, 'attack' => 19,
                    'shot' => 18, 'pass' => 14, 'dribble' => 17,
                    'block' => 15, 'intercept' => 15, 'tackle' => 19,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant grande gueule de Blue Lock. Beau parleur au niveau de jeu modeste, il compense par son énergie et sa détermination affichée.',
                ['Defender']
            ],

            [
                'Gin', 'Gagamaru', 13, 'Goalkeeper', 350,
                [
                    'speed' => 56, 'stamina' => 66, 'defense' => 24, 'attack' => 12,
                    'shot' => 10, 'pass' => 14, 'dribble' => 12,
                    'block' => 22, 'intercept' => 20, 'tackle' => 16,
                    'hand_save' => 28, 'punch_save' => 26,
                ],
                'Reconverti gardien de but après avoir été attaquant. Sa polyvalence et ses réflexes surprenants lui permettent de réaliser des arrêts décisifs dans les grands matchs.'
            ],

        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public static function specialMoves(): array
    {
        return [
            // BLUE LOCK
            // =======================

            'yoichi-isagi' => [[
                'key'         => 'isagi_direct_shot',
                'mode'        => 'attack',
                'label'       => 'Direct Shot',
                'short_label' => 'Direct',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Grâce à sa Méta-Vision, Isagi reprend une passe en une touche et frappe avant que la défense ne réagisse.',
            ]],

            'meguru-bachira' => [[
                'key'         => 'bachira_monster_dribble',
                'mode'        => 'attack',
                'label'       => 'Dribble du monstre',
                'short_label' => 'Monstre',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Bachira suit son instinct et enchaîne des dribbles imprévisibles qui sèment le chaos dans la défense.',
            ]],

            'hyoma-chigiri' => [[
                'key'         => 'chigiri_speed_burst',
                'mode'        => 'attack',
                'label'       => 'Accélération fulgurante',
                'short_label' => 'Vitesse',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Une pointe de vitesse phénoménale qui grille net la défense sur l’aile.',
            ]],

            'rensuke-kunigami' => [[
                'key'         => 'kunigami_hunter_shot',
                'mode'        => 'attack',
                'label'       => 'Tir du Chasseur',
                'short_label' => 'Chasseur',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Sa frappe gauche dévastatrice de longue distance, marque de fabrique du « Hunter ».',
            ]],

            'seishiro-nagi' => [[
                'key'         => 'nagi_god_trap_volley',
                'mode'        => 'attack',
                'label'       => 'Volée sur amorti divin',
                'short_label' => 'Amorti',
                'cooldown'    => 2,
                'base_action' => 'shot',
                'description' => 'Un contrôle parfait suivi d’une reprise immédiate, son génie naturel du ballon en un geste.',
            ]],

            'reo-mikage' => [[
                'key'         => 'mikage_perfect_playmaker',
                'mode'        => 'attack',
                'label'       => 'Meneur parfait',
                'short_label' => 'Maestro',
                'cooldown'    => 2,
                'base_action' => 'pass',
                'description' => 'Polyvalent et intelligent, Mikage délivre la passe idéale qui met l’attaque en position de marquer.',
            ]],

            'shoei-baro' => [[
                'key'         => 'baro_king_impact',
                'mode'        => 'attack',
                'label'       => 'King’s Impact',
                'short_label' => 'King',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'La frappe surpuissante du « Roi du terrain », misant sur sa puissance physique écrasante.',
            ]],

            'rin-itoshi' => [[
                'key'         => 'itoshi_flow_shot',
                'mode'        => 'attack',
                'label'       => 'Tir en état de Flow',
                'short_label' => 'Flow',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Dans son état de flow, Itoshi enchaîne lecture du jeu et frappe à un niveau supérieur.',
            ]],

            'ryusei-shido' => [[
                'key'         => 'shido_acrobatic_volley',
                'mode'        => 'attack',
                'label'       => 'Volée acrobatique',
                'short_label' => 'Acrobatie',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Une reprise de volée imprévisible rendue possible par sa souplesse hors norme.',
            ]],

            'jingo-raichi' => [[
                'key'         => 'raichi_power_charge',
                'mode'        => 'attack',
                'label'       => 'Charge bulldozer',
                'short_label' => 'Charge',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Une percée physique pleine d’énergie pour forcer le passage à travers la défense.',
            ]],

            'gin-gagamaru' => [[
                'key'         => 'gagamaru_octopus_save',
                'mode'        => 'defense',
                'label'       => 'Arrêt de la pieuvre',
                'short_label' => 'Pieuvre',
                'cooldown'    => 2,
                'base_action' => 'hand_save',
                'description' => 'Une allonge surprenante et des réflexes d’ancien attaquant pour détourner un tir surpuissant.',
            ]],

            // =======================
        ];
    }
}

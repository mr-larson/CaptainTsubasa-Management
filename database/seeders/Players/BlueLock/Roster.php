<?php

namespace Database\Seeders\Players\BlueLock;

/**
 * Données joueurs (et special moves) — groupe : Roster.
 * Extrait de PlayerSeeder pour alléger et organiser par source.
 */
class Roster
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
                    'speed' => 70, 'stamina' => 72, 'defense' => 23, 'attack' => 33,
                    'shot' => 28, 'pass' => 28, 'dribble' => 26,
                    'block' => 17, 'intercept' => 27, 'tackle' => 21,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Isagi brille par sa lecture du jeu et sa capacité à analyser le terrain pour être toujours au bon endroit au bon moment.',
                ['Forward']
            ],

            [
                'Meguru', 'Bachira', 13, 'Midfielder', 385,
                [
                    'speed' => 70, 'stamina' => 68, 'defense' => 18, 'attack' => 32,
                    'shot' => 22, 'pass' => 26, 'dribble' => 30,
                    'block' => 15, 'intercept' => 18, 'tackle' => 16,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Milieu offensif instinctif, Bachira joue en suivant son instinct et ses dribbles imprévisibles créent le chaos dans les défenses adverses.'
            ],

            [
                'Hyoma', 'Chigiri', 13, 'Midfielder', 390,
                [
                    'speed' => 84, 'stamina' => 64, 'defense' => 16, 'attack' => 32,
                    'shot' => 24, 'pass' => 20, 'dribble' => 26,
                    'block' => 13, 'intercept' => 16, 'tackle' => 14,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Ailier prodige doté d\'une vitesse à couper le souffle. Fragilisé par une blessure au genou, il se surpasse malgré tout pour devenir un joueur exceptionnel.',
                ['Forward', 'Defender']
            ],

            [
                'Rensuke', 'Kunigami', 13, 'Forward', 388,
                [
                    'speed' => 66, 'stamina' => 76, 'defense' => 20, 'attack' => 34,
                    'shot' => 28, 'pass' => 19, 'dribble' => 20,
                    'block' => 16, 'intercept' => 17, 'tackle' => 18,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant puissant spécialiste des frappes longues. Son tir gauche dévastateur de 27 mètres est sa marque de fabrique. Il rêve de devenir un super-héros du football.'
            ],

            [
                'Seishiro', 'Nagi', 13, 'Forward', 415,
                [
                    'speed' => 70, 'stamina' => 64, 'defense' => 18, 'attack' => 36,
                    'shot' => 28, 'pass' => 28, 'dribble' => 31,
                    'block' => 15, 'intercept' => 20, 'tackle' => 15,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Génie naturel du football découvert par Reo. Ses amortis et son contrôle de balle exceptionnels font de lui un joueur unique, capable de dominer sans effort apparent.',
                ['Midfielder']
            ],

            [
                'Reo', 'Mikage', 13, 'Midfielder', 378,
                [
                    'speed' => 62, 'stamina' => 68, 'defense' => 24, 'attack' => 26,
                    'shot' => 21, 'pass' => 27, 'dribble' => 22,
                    'block' => 15, 'intercept' => 24, 'tackle' => 21,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Héritier de la Mikage Corporation. Polyvalent et intelligent, il a tout pour réussir et son seul désir est de gagner la Coupe du monde avec Nagi.',
                ['Defender']
            ],

            [
                'Shoei', 'Baro', 13, 'Forward', 420,
                [
                    'speed' => 72, 'stamina' => 74, 'defense' => 20, 'attack' => 38,
                    'shot' => 33, 'pass' => 16, 'dribble' => 28,
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
                    'speed' => 72, 'stamina' => 74, 'defense' => 16, 'attack' => 36,
                    'shot' => 29, 'pass' => 20, 'dribble' => 21,
                    'block' => 13, 'intercept' => 14, 'tackle' => 14,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Attaquant explosif de Blue Lock, Shido possède un instinct du but exceptionnel dans la surface. Ses reprises acrobatiques et son engagement physique en font un finisseur redoutable, malgré son tempérament provocateur.'
            ],

            [
                'Jingo', 'Raichi', 13, 'Forward', 295,
                [
                    'speed' => 60, 'stamina' => 80, 'defense' => 27, 'attack' => 19,
                    'shot' => 18, 'pass' => 14, 'dribble' => 17,
                    'block' => 15, 'intercept' => 23, 'tackle' => 26,
                    'hand_save' => 10, 'punch_save' => 10,
                ],
                'Joueur combatif de Blue Lock, Raichi se distingue par son endurance et son marquage tenace. Moins créatif avec le ballon, il use ses adversaires par son engagement et sa présence dans les duels.',
                ['Defender']
            ],

            [
                'Gin', 'Gagamaru', 13, 'Goalkeeper', 350,
                [
                    'speed' => 63, 'stamina' => 72, 'defense' => 24, 'attack' => 12,
                    'shot' => 10, 'pass' => 14, 'dribble' => 12,
                    'block' => 23, 'intercept' => 22, 'tackle' => 16,
                    'hand_save' => 29, 'punch_save' => 27,
                ],
                'Reconverti gardien de but après avoir été attaquant. Sa polyvalence et ses réflexes surprenants lui permettent de réaliser des arrêts décisifs dans les grands matchs.'
            ],

            [
                'Sae', 'Itoshi', 13, 'Midfielder', 0,
                [
                    'speed' => 72, 'stamina' => 76, 'defense' => 26, 'attack' => 36, 'shot' => 30, 'pass' => 34, 'dribble' => 32, 'block' => 18, 'intercept' => 26, 'tackle' => 22, 'hand_save' => 10, 'punch_save' => 10,
                ],
                'Frère aîné de Rin et meilleur milieu de sa génération, passé par l’Espagne : Sae joue avec un calme glacial, une vision et une précision de passe hors normes, et dicte le rythme de tout un match.',
                ['Forward']
            ],

            [
                'Noel', 'Noa', 13, 'Forward', 0,
                [
                    'speed' => 74, 'stamina' => 78, 'defense' => 24, 'attack' => 38, 'shot' => 32, 'pass' => 30, 'dribble' => 32, 'block' => 18, 'intercept' => 22, 'tackle' => 22, 'hand_save' => 10, 'punch_save' => 10,
                ],
                'Considéré comme le meilleur attaquant du monde : Noa combine puissance, vitesse, technique et sang-froid, et sert de modèle à toute la génération Blue Lock.',
                ['Midfielder']
            ],

            [
                'Michael', 'Kaiser', 13, 'Forward', 0,
                [
                    'speed' => 72, 'stamina' => 72, 'defense' => 22, 'attack' => 37, 'shot' => 31, 'pass' => 28, 'dribble' => 30, 'block' => 17, 'intercept' => 20, 'tackle' => 20, 'hand_save' => 10, 'punch_save' => 10,
                ],
                'Prodige allemand du Bayern, surnommé l’Empereur : attaquant brillant et dominateur, sûr de lui jusqu’à l’arrogance, dont les frappes et les dribbles font la loi dans la surface.',
                ['Midfielder']
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

    /**
     * Exceptions de nationalité : les autres joueurs sont japonais par défaut (origin blue_lock).
     *
     * @return array<string, string>
     */
    public static function nationalities(): array
    {
        return [
            'noel-noa' => 'France',
            'michael-kaiser' => 'Allemagne',
        ];
    }
}

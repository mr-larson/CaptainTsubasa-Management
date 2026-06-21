<?php

namespace Database\Seeders\Players;

/**
 * Données joueurs (et special moves) — groupe : stars internationales de
 * l'univers Captain Tsubasa (rivaux étrangers du World Youth / films).
 *
 * Ces joueurs n'ont PAS de contrat de club : ils rejoignent le marché des
 * agents libres de l'origine `captain_tsubasa` et, surtout, ils sont
 * sélectionnables en équipe nationale (mode Coupe du Monde) via leur
 * `nationality` (cf. nationalities()).
 *
 * NB : effectifs « noyau canonique » destinés à être complétés — l'assembleur
 * de sélection nationale (Phase 3) comble les places manquantes. Stats calées
 * sur l'échelle des meilleurs joueurs collège (Tsubasa ~attack 39 / Hyuga 42).
 */
class CaptainTsubasaInternationalPlayers
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            // =======================
            // ALLEMAGNE
            // =======================
            [
                'Karl-Heinz', 'Schneider', 13, 'Forward', 500,
                [
                    'speed' => 84, 'stamina' => 88, 'defense' => 26, 'attack' => 46,
                    'shot' => 38, 'pass' => 25, 'dribble' => 30,
                    'block' => 20, 'intercept' => 22, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Le « Kaiser » du football allemand. Buteur d’exception au tir surpuissant, il incarne la rigueur et la puissance de la sélection.',
            ],
            [
                'Hermann', 'Kaltz', 13, 'Defender', 430,
                [
                    'speed' => 78, 'stamina' => 84, 'defense' => 42, 'attack' => 30,
                    'shot' => 24, 'pass' => 28, 'dribble' => 26,
                    'block' => 32, 'intercept' => 32, 'tackle' => 33,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Latéral offensif infatigable, il monte sans cesse pour soutenir l’attaque et combine avec Schneider.',
                ['Midfielder'],
            ],
            [
                'Manfred', 'Margus', 13, 'Defender', 380,
                [
                    'speed' => 72, 'stamina' => 80, 'defense' => 40, 'attack' => 22,
                    'shot' => 18, 'pass' => 22, 'dribble' => 20,
                    'block' => 33, 'intercept' => 30, 'tackle' => 32,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur central solide et discipliné, pilier de l’arrière-garde allemande.',
            ],
            [
                'Christian', 'Bauer', 13, 'Goalkeeper', 340,
                [
                    'speed' => 62, 'stamina' => 78, 'defense' => 38, 'attack' => 16,
                    'shot' => 14, 'pass' => 18, 'dribble' => 16,
                    'block' => 26, 'intercept' => 24, 'tackle' => 20,
                    'hand_save' => 40, 'punch_save' => 36,
                ],
                'Gardien rigoureux et bien placé, dernier rempart fiable de la sélection allemande.',
            ],

            // =======================
            // FRANCE
            // =======================
            [
                'El Sid', 'Pierre', 13, 'Midfielder', 470,
                [
                    'speed' => 82, 'stamina' => 84, 'defense' => 26, 'attack' => 38,
                    'shot' => 30, 'pass' => 33, 'dribble' => 34,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Meneur virevoltant des « Jumeaux de l’aigle ». Dribble et vision exceptionnels, il orchestre le jeu français avec Napoléon.',
                ['Forward'],
            ],
            [
                'Louis', 'Napoleon', 13, 'Forward', 460,
                [
                    'speed' => 80, 'stamina' => 82, 'defense' => 24, 'attack' => 44,
                    'shot' => 36, 'pass' => 28, 'dribble' => 31,
                    'block' => 20, 'intercept' => 22, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Attaquant tranchant, partenaire de Pierre. Leur une-deux « de l’aigle » déchire les défenses.',
            ],
            [
                'Lucien', 'Lacombe', 13, 'Defender', 360,
                [
                    'speed' => 74, 'stamina' => 80, 'defense' => 39, 'attack' => 22,
                    'shot' => 18, 'pass' => 24, 'dribble' => 22,
                    'block' => 31, 'intercept' => 30, 'tackle' => 31,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur élégant et rapide, à l’aise dans la relance autant que dans le duel.',
            ],

            // =======================
            // BRÉSIL
            // =======================
            [
                'Carlos', 'Santana', 13, 'Forward', 500,
                [
                    'speed' => 86, 'stamina' => 88, 'defense' => 26, 'attack' => 47,
                    'shot' => 38, 'pass' => 28, 'dribble' => 34,
                    'block' => 20, 'intercept' => 22, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Prodige brésilien, capitaine de la Seleção. Technique féline et finition redoutable, c’est le grand rival de Tsubasa.',
            ],
            [
                'Toninho', 'Leite', 13, 'Midfielder', 400,
                [
                    'speed' => 80, 'stamina' => 82, 'defense' => 26, 'attack' => 36,
                    'shot' => 30, 'pass' => 32, 'dribble' => 35,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu samba aux dribbles imprévisibles, il fait vivre le ballon au cœur du jeu brésilien.',
            ],
            [
                'Amaral', 'Ferreira', 13, 'Defender', 370,
                [
                    'speed' => 76, 'stamina' => 82, 'defense' => 40, 'attack' => 24,
                    'shot' => 20, 'pass' => 24, 'dribble' => 24,
                    'block' => 32, 'intercept' => 31, 'tackle' => 32,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur athlétique et anticipateur, mur de la défense brésilienne.',
            ],

            // =======================
            // ARGENTINE
            // =======================
            [
                'Juan', 'Diaz', 13, 'Forward', 480,
                [
                    'speed' => 82, 'stamina' => 84, 'defense' => 24, 'attack' => 45,
                    'shot' => 37, 'pass' => 26, 'dribble' => 32,
                    'block' => 20, 'intercept' => 22, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Buteur argentin au sang-froid glacial, son tir foudroyant fait trembler les filets.',
            ],
            [
                'Ramon', 'Galvan', 13, 'Defender', 370,
                [
                    'speed' => 75, 'stamina' => 81, 'defense' => 40, 'attack' => 24,
                    'shot' => 20, 'pass' => 24, 'dribble' => 24,
                    'block' => 32, 'intercept' => 30, 'tackle' => 33,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur accrocheur et roublard, spécialiste du marquage individuel.',
            ],
            [
                'Juan', 'Babington', 13, 'Midfielder', 390,
                [
                    'speed' => 78, 'stamina' => 83, 'defense' => 28, 'attack' => 35,
                    'shot' => 29, 'pass' => 32, 'dribble' => 31,
                    'block' => 24, 'intercept' => 26, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu complet, relais entre défense et attaque, moteur du collectif argentin.',
            ],

            // =======================
            // URUGUAY
            // =======================
            [
                'Ramon', 'Victorino', 13, 'Forward', 470,
                [
                    'speed' => 80, 'stamina' => 86, 'defense' => 26, 'attack' => 45,
                    'shot' => 37, 'pass' => 26, 'dribble' => 30,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Avant-centre uruguayen puissant et combatif, sa frappe lourde est une arme de destruction.',
            ],
            [
                'Diego', 'Madero', 13, 'Defender', 360,
                [
                    'speed' => 73, 'stamina' => 82, 'defense' => 39, 'attack' => 24,
                    'shot' => 20, 'pass' => 23, 'dribble' => 22,
                    'block' => 31, 'intercept' => 29, 'tackle' => 32,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur rugueux à la « garra charrúa », il ne lâche jamais un duel.',
            ],

            // =======================
            // PAYS-BAS
            // =======================
            [
                'Brian', 'Cruyfford', 13, 'Forward', 470,
                [
                    'speed' => 84, 'stamina' => 84, 'defense' => 24, 'attack' => 44,
                    'shot' => 35, 'pass' => 30, 'dribble' => 33,
                    'block' => 20, 'intercept' => 22, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Génie du football total néerlandais, à l’aise partout devant, fin technicien et meneur d’hommes.',
                ['Midfielder'],
            ],
            [
                'Robin', 'Van Basty', 13, 'Midfielder', 380,
                [
                    'speed' => 78, 'stamina' => 82, 'defense' => 28, 'attack' => 34,
                    'shot' => 30, 'pass' => 33, 'dribble' => 30,
                    'block' => 24, 'intercept' => 26, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu intelligent et altruiste, métronome du jeu de position oranje.',
            ],
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public static function specialMoves(): array
    {
        return [
            'karl-heinz-schneider' => [[
                'key'         => 'schneider_fire_shot',
                'mode'        => 'attack',
                'label'       => 'Tir de feu',
                'short_label' => 'Feu',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Une frappe d’une puissance phénoménale qui semble embraser le ballon.',
            ]],

            'el-sid-pierre' => [[
                'key'         => 'pierre_eagle_dribble',
                'mode'        => 'attack',
                'label'       => 'Dribble de l’aigle',
                'short_label' => 'Aigle',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Une accélération aérienne qui efface l’adversaire comme un aigle fond sur sa proie.',
            ]],

            'louis-napoleon' => [[
                'key'         => 'napoleon_eagle_shoot',
                'mode'        => 'attack',
                'label'       => 'Tir de l’aigle',
                'short_label' => 'Aigle',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'La conclusion de la combinaison des jumeaux de l’aigle, imparable lancée à pleine vitesse.',
            ]],

            'carlos-santana' => [[
                'key'         => 'santana_samba_strike',
                'mode'        => 'attack',
                'label'       => 'Frappe samba',
                'short_label' => 'Samba',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Un tir acrobatique d’une technique brésilienne stupéfiante, impossible à anticiper.',
            ]],

            'juan-diaz' => [[
                'key'         => 'diaz_condor_shot',
                'mode'        => 'attack',
                'label'       => 'Tir du condor',
                'short_label' => 'Condor',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Une frappe sèche et fulgurante qui fond sur le but comme un condor.',
            ]],

            'ramon-victorino' => [[
                'key'         => 'victorino_garra_shot',
                'mode'        => 'attack',
                'label'       => 'Tir de la garra',
                'short_label' => 'Garra',
                'cooldown'    => 3,
                'base_action' => 'shot',
                'description' => 'Une frappe lourde et rageuse portée par la hargne uruguayenne.',
            ]],

            'brian-cruyfford' => [[
                'key'         => 'cruyfford_total_turn',
                'mode'        => 'attack',
                'label'       => 'Pivot total',
                'short_label' => 'Pivot',
                'cooldown'    => 2,
                'base_action' => 'dribble',
                'description' => 'Un crochet venu du football total qui renverse l’orientation du jeu en un instant.',
            ]],
        ];
    }

    /**
     * Nationalité de chaque star internationale (slug "prenom-nom" → pays).
     * Toujours renseignée ici (aucun défaut d'origin ne s'applique).
     * @return array<string, string>
     */
    public static function nationalities(): array
    {
        return [
            // Allemagne
            'karl-heinz-schneider' => 'Allemagne',
            'hermann-kaltz'        => 'Allemagne',
            'manfred-margus'       => 'Allemagne',
            'christian-bauer'      => 'Allemagne',
            // France
            'el-sid-pierre'        => 'France',
            'louis-napoleon'       => 'France',
            'lucien-lacombe'       => 'France',
            // Brésil
            'carlos-santana'       => 'Brésil',
            'toninho-leite'        => 'Brésil',
            'amaral-ferreira'      => 'Brésil',
            // Argentine
            'juan-diaz'            => 'Argentine',
            'ramon-galvan'         => 'Argentine',
            'juan-babington'       => 'Argentine',
            // Uruguay
            'ramon-victorino'      => 'Uruguay',
            'diego-madero'         => 'Uruguay',
            // Pays-Bas
            'brian-cruyfford'      => 'Pays-Bas',
            'robin-van-basty'      => 'Pays-Bas',
        ];
    }
}

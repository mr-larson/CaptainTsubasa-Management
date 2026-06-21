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
                    'speed' => 77, 'stamina' => 84, 'defense' => 25, 'attack' => 42,
                    'shot' => 31, 'pass' => 24, 'dribble' => 28,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Le « Kaiser » du football allemand. Buteur d’exception au tir surpuissant, il incarne la rigueur et la puissance de la sélection.',
            ],
            [
                'Hermann', 'Kaltz', 13, 'Defender', 430,
                [
                    'speed' => 72, 'stamina' => 80, 'defense' => 39, 'attack' => 28,
                    'shot' => 21, 'pass' => 27, 'dribble' => 24,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Latéral offensif infatigable, il monte sans cesse pour soutenir l’attaque et combine avec Schneider.',
                ['Midfielder'],
            ],
            [
                'Manfred', 'Margus', 13, 'Defender', 380,
                [
                    'speed' => 66, 'stamina' => 76, 'defense' => 38, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 18,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur central solide et discipliné, pilier de l’arrière-garde allemande.',
            ],
            [
                'Christian', 'Bauer', 13, 'Goalkeeper', 340,
                [
                    'speed' => 57, 'stamina' => 74, 'defense' => 36, 'attack' => 15,
                    'shot' => 12, 'pass' => 17, 'dribble' => 15,
                    'block' => 25, 'intercept' => 23, 'tackle' => 19,
                    'hand_save' => 30, 'punch_save' => 30,
                ],
                'Gardien rigoureux et bien placé, dernier rempart fiable de la sélection allemande.',
            ],

            // =======================
            // FRANCE
            // =======================
            [
                'El Sid', 'Pierre', 13, 'Midfielder', 470,
                [
                    'speed' => 75, 'stamina' => 80, 'defense' => 25, 'attack' => 35,
                    'shot' => 26, 'pass' => 30, 'dribble' => 30,
                    'block' => 21, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Meneur virevoltant des « Jumeaux de l’aigle ». Dribble et vision exceptionnels, il orchestre le jeu français avec Napoléon.',
                ['Forward'],
            ],
            [
                'Louis', 'Napoleon', 13, 'Forward', 460,
                [
                    'speed' => 74, 'stamina' => 78, 'defense' => 23, 'attack' => 40,
                    'shot' => 31, 'pass' => 27, 'dribble' => 29,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Attaquant tranchant, partenaire de Pierre. Leur une-deux « de l’aigle » déchire les défenses.',
            ],
            [
                'Lucien', 'Lacombe', 13, 'Defender', 360,
                [
                    'speed' => 68, 'stamina' => 76, 'defense' => 37, 'attack' => 20,
                    'shot' => 16, 'pass' => 23, 'dribble' => 20,
                    'block' => 29, 'intercept' => 28, 'tackle' => 29,
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
                    'speed' => 79, 'stamina' => 84, 'defense' => 25, 'attack' => 42,
                    'shot' => 31, 'pass' => 27, 'dribble' => 30,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Prodige brésilien, capitaine de la Seleção. Technique féline et finition redoutable, c’est le grand rival de Tsubasa.',
            ],
            [
                'Toninho', 'Leite', 13, 'Midfielder', 400,
                [
                    'speed' => 74, 'stamina' => 78, 'defense' => 25, 'attack' => 33,
                    'shot' => 26, 'pass' => 30, 'dribble' => 30,
                    'block' => 21, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu samba aux dribbles imprévisibles, il fait vivre le ballon au cœur du jeu brésilien.',
            ],
            [
                'Amaral', 'Ferreira', 13, 'Defender', 370,
                [
                    'speed' => 70, 'stamina' => 78, 'defense' => 38, 'attack' => 22,
                    'shot' => 18, 'pass' => 23, 'dribble' => 22,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
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
                    'speed' => 75, 'stamina' => 80, 'defense' => 23, 'attack' => 41,
                    'shot' => 31, 'pass' => 25, 'dribble' => 29,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Buteur argentin au sang-froid glacial, son tir foudroyant fait trembler les filets.',
            ],
            [
                'Ramon', 'Galvan', 13, 'Defender', 370,
                [
                    'speed' => 69, 'stamina' => 77, 'defense' => 38, 'attack' => 22,
                    'shot' => 18, 'pass' => 23, 'dribble' => 22,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur accrocheur et roublard, spécialiste du marquage individuel.',
            ],
            [
                'Juan', 'Babington', 13, 'Midfielder', 390,
                [
                    'speed' => 72, 'stamina' => 79, 'defense' => 27, 'attack' => 32,
                    'shot' => 26, 'pass' => 30, 'dribble' => 29,
                    'block' => 23, 'intercept' => 25, 'tackle' => 25,
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
                    'speed' => 74, 'stamina' => 82, 'defense' => 25, 'attack' => 41,
                    'shot' => 31, 'pass' => 25, 'dribble' => 28,
                    'block' => 21, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Avant-centre uruguayen puissant et combatif, sa frappe lourde est une arme de destruction.',
            ],
            [
                'Diego', 'Madero', 13, 'Defender', 360,
                [
                    'speed' => 67, 'stamina' => 78, 'defense' => 37, 'attack' => 22,
                    'shot' => 18, 'pass' => 22, 'dribble' => 20,
                    'block' => 29, 'intercept' => 28, 'tackle' => 30,
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
                    'speed' => 77, 'stamina' => 80, 'defense' => 23, 'attack' => 40,
                    'shot' => 31, 'pass' => 29, 'dribble' => 30,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Génie du football total néerlandais, à l’aise partout devant, fin technicien et meneur d’hommes.',
                ['Midfielder'],
            ],
            [
                'Robin', 'Van Basty', 13, 'Midfielder', 380,
                [
                    'speed' => 72, 'stamina' => 78, 'defense' => 27, 'attack' => 31,
                    'shot' => 26, 'pass' => 30, 'dribble' => 28,
                    'block' => 23, 'intercept' => 25, 'tackle' => 25,
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

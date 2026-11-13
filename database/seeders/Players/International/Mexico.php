<?php

namespace Database\Seeders\Players\International;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de le Mexique.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans CaptainTsubasaInternationalPlayers::nationalities()). Format d'un joueur :
 * [prénom, nom, âge, poste, coût de base, stats, description, postes secondaires?].
 * L'ordre fixe les ids en base.
 */
final class Mexico
{
    /**
     * Joueurs de le Mexique, gardien(s) en premier puis défenseurs, milieux, attaquants.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function players(): array
    {
        return [
            // --- Gardien ---
            [
                'Ricardo', 'Espadas', 13, 'Goalkeeper', 320,
                [
                    'speed' => 58, 'stamina' => 74, 'defense' => 36, 'attack' => 15,
                    'shot' => 12, 'pass' => 17, 'dribble' => 15,
                    'block' => 25, 'intercept' => 23, 'tackle' => 19,
                    'hand_save' => 28, 'punch_save' => 27,
                ],
                'Capitaine et gardien émérite du Mexique, vif sur sa ligne et meneur d’hommes respecté.',
            ],

            // --- Défenseurs ---
            [
                '', 'Medina', 13, 'Defender', 360,
                [
                    'speed' => 67, 'stamina' => 76, 'defense' => 35, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 20,
                    'block' => 27, 'intercept' => 25, 'tackle' => 27,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur solide et combatif, taulier de l’arrière-garde mexicaine.',
            ],
            [
                '', 'Carvajal', 13, 'Defender', 360,
                [
                    'speed' => 68, 'stamina' => 76, 'defense' => 35, 'attack' => 21,
                    'shot' => 16, 'pass' => 22, 'dribble' => 21,
                    'block' => 27, 'intercept' => 25, 'tackle' => 27,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Latéral offensif endurant, il apporte le surnombre sur son couloir.',
            ],
            [
                '', 'Gomez', 13, 'Defender', 350,
                [
                    'speed' => 66, 'stamina' => 75, 'defense' => 34, 'attack' => 19,
                    'shot' => 15, 'pass' => 20, 'dribble' => 19,
                    'block' => 26, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur rugueux au sens du placement, difficile à déborder.',
            ],
            [
                '', 'Espino', 13, 'Defender', 345,
                [
                    'speed' => 66, 'stamina' => 74, 'defense' => 34, 'attack' => 20,
                    'shot' => 15, 'pass' => 20, 'dribble' => 20,
                    'block' => 26, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur discipliné, précieux dans l’organisation de la ligne mexicaine.',
            ],
            [
                '', 'Rivera', 13, 'Defender', 340,
                [
                    'speed' => 65, 'stamina' => 75, 'defense' => 33, 'attack' => 19,
                    'shot' => 15, 'pass' => 20, 'dribble' => 19,
                    'block' => 26, 'intercept' => 24, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur appliqué et généreux dans l’effort, fiable dans les duels.',
            ],

            // --- Milieux ---
            [
                '', 'Zaragoza', 13, 'Midfielder', 410,
                [
                    'speed' => 70, 'stamina' => 78, 'defense' => 27, 'attack' => 31,
                    'shot' => 25, 'pass' => 28, 'dribble' => 27,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Meneur de jeu mexicain à la technique soignée, cerveau de la Tricolor.',
            ],
            [
                '', 'Garcia', 13, 'Midfielder', 385,
                [
                    'speed' => 69, 'stamina' => 77, 'defense' => 28, 'attack' => 29,
                    'shot' => 23, 'pass' => 27, 'dribble' => 26,
                    'block' => 23, 'intercept' => 25, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu travailleur et précis, liant solide entre défense et attaque.',
            ],
            [
                '', 'Suarez', 13, 'Midfielder', 380,
                [
                    'speed' => 68, 'stamina' => 76, 'defense' => 26, 'attack' => 28,
                    'shot' => 23, 'pass' => 26, 'dribble' => 26,
                    'block' => 22, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu vif et créatif, il accélère le jeu par ses passes incisives.',
            ],

            // --- Attaquants ---
            [
                '', 'Alvez', 13, 'Forward', 440,
                [
                    'speed' => 75, 'stamina' => 79, 'defense' => 23, 'attack' => 37,
                    'shot' => 28, 'pass' => 25, 'dribble' => 28,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Buteur vedette du Mexique, opportuniste redoutable dans la surface.',
            ],
            [
                '', 'Lopez', 13, 'Forward', 425,
                [
                    'speed' => 74, 'stamina' => 78, 'defense' => 23, 'attack' => 36,
                    'shot' => 27, 'pass' => 24, 'dribble' => 27,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Attaquant remuant et adroit, partenaire de pointe complémentaire d’Alvez.',
            ],
        ];
    }
}

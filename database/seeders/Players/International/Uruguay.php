<?php

namespace Database\Seeders\Players\International;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de l'Uruguay.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans CaptainTsubasaInternationalPlayers::nationalities()). Format d'un joueur :
 * [prénom, nom, âge, poste, coût de base, stats, description, postes secondaires?].
 * L'ordre fixe les ids en base.
 */
final class Uruguay
{
    /**
     * Joueurs de l'Uruguay, gardien(s) en premier puis défenseurs, milieux, attaquants.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function players(): array
    {
        return [
            // NB : Ryoma Hino (FW #9) existe déjà comme agent libre dans
            // CaptainTsubasaFreePlayers ; il est rattaché à l'Uruguay via
            // nationalities() ci-dessous (pas de doublon ici).
            // --- Gardien ---
            [
                '', 'Conzales', 13, 'Goalkeeper', 330,
                [
                    'speed' => 60, 'stamina' => 76, 'defense' => 38, 'attack' => 16,
                    'shot' => 13, 'pass' => 18, 'dribble' => 16,
                    'block' => 26, 'intercept' => 24, 'tackle' => 20,
                    'hand_save' => 29, 'punch_save' => 28,
                ],
                'Gardien uruguayen courageux et explosif, dernier rempart de la Celeste.',
            ],

            // --- Défenseurs ---
            [
                '', 'Amerigo', 13, 'Defender', 400,
                [
                    'speed' => 70, 'stamina' => 80, 'defense' => 38, 'attack' => 22,
                    'shot' => 17, 'pass' => 23, 'dribble' => 22,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Libéro uruguayen au sang-froid, organisateur d’une défense imperméable.',
            ],
            [
                '', 'Pazu', 13, 'Defender', 380,
                [
                    'speed' => 69, 'stamina' => 80, 'defense' => 38, 'attack' => 21,
                    'shot' => 17, 'pass' => 22, 'dribble' => 21,
                    'block' => 29, 'intercept' => 27, 'tackle' => 29,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur tout en « garra charrúa », il harcèle l’attaquant jusqu’au bout.',
            ],
            [
                '', 'Olivares', 13, 'Defender', 370,
                [
                    'speed' => 68, 'stamina' => 79, 'defense' => 37, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 20,
                    'block' => 29, 'intercept' => 27, 'tackle' => 29,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur dur au mal et bon relanceur, fiable dans l’adversité.',
            ],
            [
                '', 'Filippo', 13, 'Defender', 360,
                [
                    'speed' => 67, 'stamina' => 78, 'defense' => 36, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 20,
                    'block' => 28, 'intercept' => 26, 'tackle' => 28,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur appliqué, précieux dans la couverture et le marquage.',
            ],

            // --- Milieux ---
            [
                '', 'Dionisi', 13, 'Midfielder', 420,
                [
                    'speed' => 72, 'stamina' => 80, 'defense' => 28, 'attack' => 33,
                    'shot' => 26, 'pass' => 29, 'dribble' => 28,
                    'block' => 23, 'intercept' => 25, 'tackle' => 25,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Meneur uruguayen technique et combatif, plaque tournante de la Celeste.',
            ],
            [
                '', 'Enrico', 13, 'Midfielder', 410,
                [
                    'speed' => 71, 'stamina' => 79, 'defense' => 27, 'attack' => 32,
                    'shot' => 25, 'pass' => 28, 'dribble' => 27,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu offensif inspiré, il illumine le jeu uruguayen de ses passes.',
            ],
            [
                '', 'Pedro', 13, 'Midfielder', 390,
                [
                    'speed' => 71, 'stamina' => 80, 'defense' => 29, 'attack' => 31,
                    'shot' => 25, 'pass' => 28, 'dribble' => 27,
                    'block' => 24, 'intercept' => 26, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu box-to-box infatigable, poumon de l’entrejeu uruguayen.',
            ],
            [
                '', 'Fengas', 13, 'Midfielder', 380,
                [
                    'speed' => 70, 'stamina' => 79, 'defense' => 29, 'attack' => 30,
                    'shot' => 24, 'pass' => 27, 'dribble' => 26,
                    'block' => 24, 'intercept' => 26, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Récupérateur tenace, il gratte tous les ballons devant la défense.',
            ],

            // --- Attaquant (capitaine) ---
            [
                'Ramon', 'Victorino', 13, 'Forward', 470,
                [
                    'speed' => 74, 'stamina' => 82, 'defense' => 25, 'attack' => 41,
                    'shot' => 31, 'pass' => 25, 'dribble' => 28,
                    'block' => 21, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Capitaine et avant-centre de la Celeste, puissant et combatif, sa frappe lourde est une arme de destruction.',
            ],
        ];
    }
}

<?php

namespace Database\Seeders\Players\International;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de les Pays-Bas.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans CaptainTsubasaInternationalPlayers::nationalities()). Format d'un joueur :
 * [prénom, nom, âge, poste, coût de base, stats, description, postes secondaires?].
 * L'ordre fixe les ids en base.
 */
final class Netherlands
{
    /**
     * Joueurs de les Pays-Bas, gardien(s) en premier puis défenseurs, milieux, attaquants.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function players(): array
    {
        return [
            // --- Gardien ---
            [
                'Hans', 'Dolman', 13, 'Goalkeeper', 340,
                [
                    'speed' => 60, 'stamina' => 76, 'defense' => 38, 'attack' => 16,
                    'shot' => 13, 'pass' => 19, 'dribble' => 16,
                    'block' => 26, 'intercept' => 24, 'tackle' => 20,
                    'hand_save' => 30, 'punch_save' => 29,
                ],
                'Gardien de l’Ajax, sûr sur sa ligne et excellent dans le jeu au pied à la mode oranje.',
            ],

            // --- Défenseur ---
            [
                'Leon', 'Dick', 13, 'Defender', 400,
                [
                    'speed' => 70, 'stamina' => 80, 'defense' => 38, 'attack' => 23,
                    'shot' => 18, 'pass' => 24, 'dribble' => 23,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur de l’Ajax adepte du football total, solide et très à l’aise balle au pied.',
            ],

            // --- Milieux ---
            [
                'Ruud', 'Klisman', 13, 'Midfielder', 460,
                [
                    'speed' => 72, 'stamina' => 80, 'defense' => 27, 'attack' => 35,
                    'shot' => 28, 'pass' => 30, 'dribble' => 29,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Vice-capitaine et meneur de l’Ajax, technicien complet et inspirateur du jeu oranje.',
            ],
            [
                '', 'Haan', 13, 'Midfielder', 400,
                [
                    'speed' => 71, 'stamina' => 80, 'defense' => 29, 'attack' => 31,
                    'shot' => 25, 'pass' => 29, 'dribble' => 27,
                    'block' => 24, 'intercept' => 26, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu complet à la frappe lointaine redoutable, métronome de l’entrejeu néerlandais.',
            ],
            [
                'Brian', 'Cruyfford', 13, 'Midfielder', 470,
                [
                    'speed' => 77, 'stamina' => 80, 'defense' => 23, 'attack' => 40,
                    'shot' => 31, 'pass' => 29, 'dribble' => 30,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Capitaine et génie du football total néerlandais, à l’aise partout devant, fin technicien et meneur d’hommes.',
                ['Forward'],
            ],

            // --- Attaquants ---
            [
                'Johan', 'Rensenbrink', 13, 'Forward', 450,
                [
                    'speed' => 77, 'stamina' => 79, 'defense' => 23, 'attack' => 38,
                    'shot' => 29, 'pass' => 26, 'dribble' => 30,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Ailier néerlandais virevoltant, dribbleur élégant et finisseur tout en finesse.',
            ],
            [
                'Gert', 'Keizer', 13, 'Forward', 435,
                [
                    'speed' => 75, 'stamina' => 78, 'defense' => 23, 'attack' => 37,
                    'shot' => 28, 'pass' => 26, 'dribble' => 29,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Ailier rapide et technique, spécialiste des débordements et des centres millimétrés.',
            ],
        ];
    }
}

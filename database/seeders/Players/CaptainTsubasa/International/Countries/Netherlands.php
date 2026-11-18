<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de les Pays-Bas.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans Roster::nationalities()). Format d'un joueur :
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
                    'speed' => 60, 'stamina' => 72, 'defense' => 32, 'attack' => 16,
                    'shot' => 13, 'pass' => 19, 'dribble' => 16,
                    'block' => 23, 'intercept' => 21, 'tackle' => 20,
                    'hand_save' => 27, 'punch_save' => 26,
                ],
                'Gardien de l’Ajax, sûr sur sa ligne et excellent dans le jeu au pied à la mode oranje.',
            ],

            // --- Défenseur ---
            [
                'Leon', 'Dick', 13, 'Defender', 400,
                [
                    'speed' => 68, 'stamina' => 75, 'defense' => 33, 'attack' => 23,
                    'shot' => 18, 'pass' => 24, 'dribble' => 23,
                    'block' => 27, 'intercept' => 25, 'tackle' => 27,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur de l’Ajax adepte du football total, solide et très à l’aise balle au pied.',
            ],

            // --- Milieux ---
            [
                'Ruud', 'Klisman', 13, 'Midfielder', 460,
                [
                    'speed' => 70, 'stamina' => 76, 'defense' => 27, 'attack' => 33,
                    'shot' => 28, 'pass' => 28, 'dribble' => 27,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Vice-capitaine et meneur de l’Ajax, technicien complet et inspirateur du jeu oranje.',
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
                    'speed' => 75, 'stamina' => 75, 'defense' => 23, 'attack' => 35,
                    'shot' => 27, 'pass' => 25, 'dribble' => 28,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Ailier néerlandais virevoltant, dribbleur élégant et finisseur tout en finesse.',
            ],
            [
                'Gert', 'Keizer', 13, 'Forward', 435,
                [
                    'speed' => 73, 'stamina' => 74, 'defense' => 23, 'attack' => 34,
                    'shot' => 26, 'pass' => 25, 'dribble' => 27,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Ailier rapide et technique, spécialiste des débordements et des centres millimétrés.',
            ],
        ];
    }
}

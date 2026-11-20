<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — la Suède.
 * Joueur sans contrat de club : agent libre de l'origine `captain_tsubasa_international`
 * et base de la sélection nationale du mode Coupe du Monde (sa nationalité est dans
 * Roster::nationalities()). Format : [prénom, nom, âge, poste, coût de base, stats,
 * description, postes secondaires?].
 */
final class Sweden
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            [
                'Stefan', 'Levin', 13, 'Midfielder', 0,
                [
                    'speed' => 76, 'stamina' => 82, 'defense' => 25, 'attack' => 38,
                    'shot' => 34, 'pass' => 30, 'dribble' => 29,
                    'block' => 20, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Capitaine de la sélection suédoise, milieu de terrain complet et puissant. Frappe de loin redoutable et autorité naturelle : il porte son équipe sur ses épaules.',
            ],
            [
                '', 'Brolin', 13, 'Defender', 0,
                [
                    'speed' => 64, 'stamina' => 80, 'defense' => 32, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 19,
                    'block' => 27, 'intercept' => 28, 'tackle' => 28,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur de la sélection la Suède. Solide et appliqué, il ferme les espaces devant son but.',
            ],
            [
                '', 'Federiks', 13, 'Midfielder', 0,
                [
                    'speed' => 70, 'stamina' => 78, 'defense' => 23, 'attack' => 30,
                    'shot' => 25, 'pass' => 27, 'dribble' => 26,
                    'block' => 18, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu offensif de la sélection la Suède. Technique et inventif, il alimente l’attaque.',
            ],
            [
                '', 'Larson', 13, 'Midfielder', 0,
                [
                    'speed' => 71, 'stamina' => 78, 'defense' => 23, 'attack' => 30,
                    'shot' => 25, 'pass' => 27, 'dribble' => 26,
                    'block' => 18, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu offensif de la sélection la Suède. Technique et inventif, il alimente l’attaque.',
            ],
        ];
    }
}

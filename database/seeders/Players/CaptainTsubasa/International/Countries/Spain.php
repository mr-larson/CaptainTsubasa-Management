<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — l’Espagne.
 * Joueur sans contrat de club : agent libre de l'origine `captain_tsubasa_international`
 * et base de la sélection nationale du mode Coupe du Monde (sa nationalité est dans
 * Roster::nationalities()). Format : [prénom, nom, âge, poste, coût de base, stats,
 * description, postes secondaires?].
 */
final class Spain
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            [
                'Michael', 'Juanito', 13, 'Midfielder', 0,
                [
                    'speed' => 72, 'stamina' => 78, 'defense' => 24, 'attack' => 31,
                    'shot' => 28, 'pass' => 27, 'dribble' => 27,
                    'block' => 19, 'intercept' => 22, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu de terrain de la sélection espagnole, technique et inventif, capable de lier le jeu et d’ouvrir des brèches.',
            ],
            [
                '', 'Raphael', 13, 'Midfielder', 0,
                [
                    'speed' => 70, 'stamina' => 78, 'defense' => 23, 'attack' => 30,
                    'shot' => 25, 'pass' => 27, 'dribble' => 26,
                    'block' => 18, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu offensif de la sélection l’Espagne. Technique et inventif, il alimente l’attaque.',
                ['Defender'],
            ],
        ];
    }
}

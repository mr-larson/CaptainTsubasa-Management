<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — la Thaïlande.
 * Joueur sans contrat de club : agent libre de l'origine `captain_tsubasa_international`
 * et base de la sélection nationale du mode Coupe du Monde (sa nationalité est dans
 * Roster::nationalities()). Format : [prénom, nom, âge, poste, coût de base, stats,
 * description, postes secondaires?].
 */
final class Thailand
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            [
                'Singprasert', 'Bunnaak', 13, 'Defender', 0,
                [
                    'speed' => 66, 'stamina' => 80, 'defense' => 32, 'attack' => 21,
                    'shot' => 17, 'pass' => 23, 'dribble' => 21,
                    'block' => 27, 'intercept' => 28, 'tackle' => 27,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Capitaine et défenseur de la Thaïlande. Solide, courageux et organisateur de sa défense, il mène sa sélection par l’exemple.',
            ],
            [
                'Faran', 'Konsawatt', 13, 'Midfielder', 0,
                [
                    'speed' => 70, 'stamina' => 78, 'defense' => 23, 'attack' => 30,
                    'shot' => 25, 'pass' => 27, 'dribble' => 26,
                    'block' => 18, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu offensif de la sélection la Thaïlande. Technique et inventif, il alimente l’attaque.',
            ],
            [
                'Sakhon', 'Konsawatt', 13, 'Forward', 0,
                [
                    'speed' => 72, 'stamina' => 78, 'defense' => 20, 'attack' => 34,
                    'shot' => 28, 'pass' => 22, 'dribble' => 25,
                    'block' => 17, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Attaquant de la sélection la Thaïlande. Rapide et direct, il cherche sans relâche le chemin du but.',
            ],
            [
                'Chana', 'Konsawatt', 13, 'Forward', 0,
                [
                    'speed' => 70, 'stamina' => 78, 'defense' => 20, 'attack' => 33,
                    'shot' => 28, 'pass' => 22, 'dribble' => 25,
                    'block' => 17, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Attaquant de la sélection la Thaïlande. Rapide et direct, il cherche sans relâche le chemin du but.',
            ],
        ];
    }
}

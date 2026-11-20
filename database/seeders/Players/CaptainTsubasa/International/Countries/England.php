<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — l’Angleterre. Joueurs sans contrat de club : agents
 * libres de l'origine `captain_tsubasa_international` et base de la sélection nationale du mode Coupe du Monde
 * (leur nationalité est dans Roster::nationalities()). Format : [prénom, nom, âge, poste, coût de base, stats,
 * description, postes secondaires?].
 */
final class England
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            [
                '', 'Robson', 13, 'Defender', 0,
                [
                    'speed' => 64, 'stamina' => 80, 'defense' => 32, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 19,
                    'block' => 27, 'intercept' => 28, 'tackle' => 28,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur de la sélection l’Angleterre. Solide et appliqué, il ferme les espaces devant son but.',
            ],
        ];
    }
}

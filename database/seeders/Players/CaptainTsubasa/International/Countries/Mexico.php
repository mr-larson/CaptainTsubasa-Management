<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de le Mexique.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans Roster::nationalities()). Format d'un joueur :
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
                    'speed' => 58, 'stamina' => 73, 'defense' => 32, 'attack' => 15,
                    'shot' => 12, 'pass' => 17, 'dribble' => 15,
                    'block' => 25, 'intercept' => 23, 'tackle' => 19,
                    'hand_save' => 27, 'punch_save' => 26,
                ],
                'Capitaine et gardien émérite du Mexique, vif sur sa ligne et meneur d’hommes respecté.',
            ],

        ];
    }
}

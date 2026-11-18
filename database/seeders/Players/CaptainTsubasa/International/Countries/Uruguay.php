<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de l'Uruguay.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans Roster::nationalities()). Format d'un joueur :
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
            // FreePlayers ; il est rattaché à l'Uruguay via
            // nationalities() ci-dessous (pas de doublon ici).

            // --- Attaquant (capitaine) ---
            [
                'Ramon', 'Victorino', 13, 'Forward', 470,
                [
                    'speed' => 76, 'stamina' => 79, 'defense' => 25, 'attack' => 38,
                    'shot' => 29, 'pass' => 23, 'dribble' => 26,
                    'block' => 21, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Capitaine et avant-centre de la Celeste, puissant et combatif, sa frappe lourde est une arme de destruction.',
            ],
        ];
    }
}

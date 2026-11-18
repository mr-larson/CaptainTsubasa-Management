<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de l'Italie.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans Roster::nationalities()). Format d'un joueur :
 * [prénom, nom, âge, poste, coût de base, stats, description, postes secondaires?].
 * L'ordre fixe les ids en base.
 */
final class Italy
{
    /**
     * Joueurs de l'Italie, gardien(s) en premier puis défenseurs, milieux, attaquants.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function players(): array
    {
        return [
            // NB : la sélection italienne s'appuie aussi sur les joueurs de
            // l'École des Champions (origin ecole_des_champions, italiens par
            // défaut), qui restent dans le vivier de la Squadra Azzurra.
            // --- Gardiens ---
            [
                'Gino', 'Hernandez', 13, 'Goalkeeper', 340,
                [
                    'speed' => 60, 'stamina' => 76, 'defense' => 38, 'attack' => 16,
                    'shot' => 13, 'pass' => 18, 'dribble' => 16,
                    'block' => 26, 'intercept' => 24, 'tackle' => 20,
                    'hand_save' => 30, 'punch_save' => 29,
                ],
                'Capitaine et gardien de l’Inter, réflexes d’élite et autorité dans la surface azzurra.',
            ],

            // --- Défenseurs (catenaccio) ---
            [
                'Salvatore', 'Gentile', 13, 'Defender', 410,
                [
                    'speed' => 69, 'stamina' => 81, 'defense' => 38, 'attack' => 21,
                    'shot' => 17, 'pass' => 22, 'dribble' => 21,
                    'block' => 29, 'intercept' => 28, 'tackle' => 29,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur de la Juventus, marqueur intraitable et roc du catenaccio italien.',
            ],

        ];
    }
}

<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — le Danemark. Joueurs sans contrat de club : agents
 * libres de l'origine `captain_tsubasa_international` et base de la sélection nationale du mode Coupe du Monde
 * (leur nationalité est dans Roster::nationalities()). Format : [prénom, nom, âge, poste, coût de base, stats,
 * description, postes secondaires?].
 */
final class Denmark
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            [
                '', 'Haas', 13, 'Forward', 0,
                [
                    'speed' => 72, 'stamina' => 78, 'defense' => 20, 'attack' => 34,
                    'shot' => 28, 'pass' => 22, 'dribble' => 25,
                    'block' => 17, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Attaquant de la sélection le Danemark. Rapide et direct, il cherche sans relâche le chemin du but.',
            ],
            [
                '', 'Christiansen', 13, 'Forward', 0,
                [
                    'speed' => 70, 'stamina' => 78, 'defense' => 20, 'attack' => 33,
                    'shot' => 28, 'pass' => 22, 'dribble' => 25,
                    'block' => 17, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Attaquant de la sélection le Danemark. Rapide et direct, il cherche sans relâche le chemin du but.',
            ],
        ];
    }
}

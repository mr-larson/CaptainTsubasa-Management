<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de l'Argentine.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans Roster::nationalities()). Format d'un joueur :
 * [prénom, nom, âge, poste, coût de base, stats, description, postes secondaires?].
 * L'ordre fixe les ids en base.
 */
final class Argentina
{
    /**
     * Joueurs de l'Argentine, gardien(s) en premier puis défenseurs, milieux, attaquants.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function players(): array
    {
        return [
            [
                'Juan', 'Diaz', 13, 'Forward', 480,
                [
                    'speed' => 75, 'stamina' => 80, 'defense' => 23, 'attack' => 41,
                    'shot' => 31, 'pass' => 25, 'dribble' => 29,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Buteur argentin au sang-froid glacial, son tir foudroyant fait trembler les filets.',
            ],
            [
                'Ramon', 'Galvan', 13, 'Defender', 370,
                [
                    'speed' => 69, 'stamina' => 77, 'defense' => 35, 'attack' => 22,
                    'shot' => 18, 'pass' => 23, 'dribble' => 22,
                    'block' => 28, 'intercept' => 26, 'tackle' => 28,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur accrocheur et roublard, spécialiste du marquage individuel.',
            ],
        ];
    }
}

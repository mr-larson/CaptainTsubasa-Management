<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — les États-Unis.
 * Joueur sans contrat de club : agent libre de l'origine `captain_tsubasa_international`
 * et base de la sélection nationale du mode Coupe du Monde (sa nationalité est dans
 * Roster::nationalities()). Format : [prénom, nom, âge, poste, coût de base, stats,
 * description, postes secondaires?].
 */
final class UnitedStates
{
    /** @return array<int, array<int, mixed>> */
    public static function players(): array
    {
        return [
            [
                'Ryan', 'Ortiz', 13, 'Midfielder', 0,
                [
                    'speed' => 66, 'stamina' => 74, 'attack' => 27, 'defense' => 21, 'shot' => 23, 'pass' => 25, 'dribble' => 24, 'block' => 17, 'intercept' => 19, 'tackle' => 20, 'heading' => 17, 'hand_save' => 15, 'punch_save' => 15,
                ],
                'Capitaine des États-Unis (n° 10), surnommé le « Tyran du terrain » : milieu complet qui domine le jeu par sa technique, sa passe et son autorité.',
            ],
            [
                'Blake', 'Martin', 13, 'Forward', 0,
                [
                    'speed' => 68, 'stamina' => 74, 'attack' => 30, 'defense' => 16, 'shot' => 25, 'pass' => 17, 'dribble' => 20, 'block' => 14, 'intercept' => 15, 'tackle' => 16, 'heading' => 24, 'hand_save' => 15, 'punch_save' => 15,
                ],
                'Attaquant des États-Unis au physique athlétique impressionnant : il perce par la puissance et conclut du pied comme de la tête.',
            ],
            [
                'Cheikh', 'Azwan', 13, 'Goalkeeper', 0,
                [
                    'speed' => 60, 'stamina' => 70, 'attack' => 15, 'defense' => 30, 'shot' => 12, 'pass' => 19, 'dribble' => 14, 'block' => 21, 'intercept' => 21, 'tackle' => 16, 'heading' => 14, 'hand_save' => 27, 'punch_save' => 26,
                ],
                'Gardien des États-Unis, compétiteur dans l’âme : athlétique, il s’impose par sa détente et son envie de ne rien lâcher.',
            ],
            [
                'Sylvester', 'Luke', 13, 'Forward', 0,
                [
                    'speed' => 70, 'stamina' => 72, 'attack' => 29, 'defense' => 18, 'shot' => 24, 'pass' => 22, 'dribble' => 25, 'block' => 15, 'intercept' => 17, 'tackle' => 17, 'heading' => 19, 'hand_save' => 15, 'punch_save' => 15,
                ],
                'Capitaine et vedette de l’équipe américaine du film de 1986 (Sekai Daikessen Junior World Cup) et de Captain Tsubasa V : attaquant complet, vif et inspirant.',
            ],
        ];
    }
}

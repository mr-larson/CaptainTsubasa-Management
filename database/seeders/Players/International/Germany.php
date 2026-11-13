<?php

namespace Database\Seeders\Players\International;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de l'Allemagne.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans CaptainTsubasaInternationalPlayers::nationalities()). Format d'un joueur :
 * [prénom, nom, âge, poste, coût de base, stats, description, postes secondaires?].
 * L'ordre fixe les ids en base.
 */
final class Germany
{
    /**
     * Joueurs de l'Allemagne, gardien(s) en premier puis défenseurs, milieux, attaquants.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function players(): array
    {
        return [
            [
                'Karl-Heinz', 'Schneider', 13, 'Forward', 500,
                [
                    'speed' => 77, 'stamina' => 84, 'defense' => 25, 'attack' => 42,
                    'shot' => 31, 'pass' => 24, 'dribble' => 28,
                    'block' => 19, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Le « Kaiser » du football allemand. Buteur d’exception au tir surpuissant, il incarne la rigueur et la puissance de la sélection.',
            ],
            [
                'Hermann', 'Kaltz', 13, 'Defender', 430,
                [
                    'speed' => 72, 'stamina' => 80, 'defense' => 39, 'attack' => 28,
                    'shot' => 21, 'pass' => 27, 'dribble' => 24,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Latéral offensif infatigable, il monte sans cesse pour soutenir l’attaque et combine avec Schneider.',
                ['Midfielder'],
            ],
            [
                'Manfred', 'Margus', 13, 'Forward', 380,
                [
                    'speed' => 66, 'stamina' => 76, 'defense' => 38, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 18,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur central solide et discipliné, pilier de l’arrière-garde allemande.',
            ],
            [
            'Franz', 'Schester', 13, 'Midfielder', 380,
                [
                    'speed' => 66, 'stamina' => 76, 'defense' => 38, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 18,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur central solide et discipliné, pilier de l’arrière-garde allemande.',
            ],
            [
                'Deuter', 'Müller', 13, 'Goalkeeper', 340,
                [
                    'speed' => 57, 'stamina' => 74, 'defense' => 36, 'attack' => 15,
                    'shot' => 12, 'pass' => 17, 'dribble' => 15,
                    'block' => 25, 'intercept' => 23, 'tackle' => 19,
                    'hand_save' => 30, 'punch_save' => 30,
                ],
                'Gardien rigoureux et bien placé, dernier rempart fiable de la sélection allemande.',
            ],
        ];
    }
}

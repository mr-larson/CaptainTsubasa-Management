<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de l'Allemagne.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans Roster::nationalities()). Format d'un joueur :
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
                    'speed' => 72, 'stamina' => 80, 'defense' => 37, 'attack' => 28,
                    'shot' => 21, 'pass' => 27, 'dribble' => 24,
                    'block' => 28, 'intercept' => 28, 'tackle' => 29,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Latéral offensif infatigable, il monte sans cesse pour soutenir l’attaque et combine avec Schneider.',
                ['Midfielder'],
            ],
            [
                'Manfred', 'Margus', 13, 'Forward', 380,
                [
                    'speed' => 65, 'stamina' => 76, 'defense' => 20, 'attack' => 35,
                    'shot' => 28, 'pass' => 21, 'dribble' => 22,
                    'block' => 18, 'intercept' => 18, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Avant-centre allemand puissant, Margus apporte sa présence physique et son jeu aérien pour conclure les attaques.',
            ],
            [
            'Franz', 'Schester', 13, 'Midfielder', 380,
                [
                    'speed' => 70, 'stamina' => 76, 'defense' => 26, 'attack' => 32,
                    'shot' => 24, 'pass' => 28, 'dribble' => 26,
                    'block' => 20, 'intercept' => 23, 'tackle' => 22,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu technique allemand, Schester organise les attaques par ses passes et sa conduite de balle, en soutien de Schneider et Margus.',
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
            [
                'Schweil', 'Teigerbran', 13, 'Midfielder', 0,
                [
                    'speed' => 70, 'stamina' => 80, 'defense' => 23, 'attack' => 30,
                    'shot' => 25, 'pass' => 27, 'dribble' => 26,
                    'block' => 18, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu offensif de la sélection l’Allemagne. Technique et inventif, il alimente l’attaque.',
                ['Defender'],
            ],
            [
                'Kevin', 'Schmidt', 13, 'Midfielder', 0,
                [
                    'speed' => 66, 'stamina' => 80, 'defense' => 29, 'attack' => 24,
                    'shot' => 20, 'pass' => 25, 'dribble' => 21,
                    'block' => 24, 'intercept' => 26, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu défensif de la sélection l’Allemagne. Récupérateur infatigable, il protège sa défense.',
                ['Defender'],
            ],
            [
                'Eric', 'Schmidt', 13, 'Midfielder', 0,
                [
                    'speed' => 67, 'stamina' => 80, 'defense' => 29, 'attack' => 24,
                    'shot' => 20, 'pass' => 25, 'dribble' => 21,
                    'block' => 24, 'intercept' => 26, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu défensif de la sélection l’Allemagne. Récupérateur infatigable, il protège sa défense.',
                ['Defender'],
            ],
            [
                'Mario', 'Goethe', 13, 'Forward', 0,
                [
                    'speed' => 72, 'stamina' => 78, 'defense' => 20, 'attack' => 34,
                    'shot' => 28, 'pass' => 22, 'dribble' => 25,
                    'block' => 17, 'intercept' => 19, 'tackle' => 19,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Attaquant de la sélection l’Allemagne. Rapide et direct, il cherche sans relâche le chemin du but.',
            ],
        ];
    }
}

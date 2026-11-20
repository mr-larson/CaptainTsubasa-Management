<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau du Mexique.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans Roster::nationalities()). Format d'un joueur :
 * [prénom, nom, âge, poste, coût de base, stats, description, postes secondaires?].
 * L'ordre fixe les ids en base.
 */
final class Mexico
{
    /**
     * Joueurs du Mexique, gardien(s) en premier puis défenseurs, milieux, attaquants.
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
            // Les cinq guerriers du Soleil aztèque, compagnons d'Espadas.
            ['', 'Zaragoza', 13, 'Midfielder', 0,
                ['speed' => 68, 'stamina' => 74, 'attack' => 29, 'defense' => 27, 'shot' => 25, 'pass' => 26, 'dribble' => 25, 'block' => 22, 'intercept' => 24, 'tackle' => 24, 'heading' => 18, 'hand_save' => 15, 'punch_save' => 15],
                'Milieu du Mexique Youth et membre des cinq guerriers du Soleil aztèque. Ami d’enfance d’Espadas, Zaragoza participe aux combinaisons collectives du groupe.'],
            ['', 'Garcia', 13, 'Midfielder', 0,
                ['speed' => 67, 'stamina' => 73, 'attack' => 27, 'defense' => 28, 'shot' => 23, 'pass' => 25, 'dribble' => 24, 'block' => 23, 'intercept' => 25, 'tackle' => 24, 'heading' => 18, 'hand_save' => 15, 'punch_save' => 15],
                'Milieu physique du Mexique Youth, Garcia apporte sa puissance aux cinq guerriers du Soleil aztèque. Son gabarit soutient leurs combinaisons inspirées de la lucha libre.'],
            ['', 'Suarez', 13, 'Midfielder', 0,
                ['speed' => 66, 'stamina' => 72, 'attack' => 26, 'defense' => 26, 'shot' => 23, 'pass' => 24, 'dribble' => 24, 'block' => 22, 'intercept' => 23, 'tackle' => 23, 'heading' => 18, 'hand_save' => 15, 'punch_save' => 15],
                'Milieu du Mexique Youth et compagnon d’Espadas. Suarez contribue aux actions combinées des cinq guerriers du Soleil aztèque, travaillées dans leur salle de lucha libre.'],
            ['', 'Alvez', 13, 'Forward', 0,
                ['speed' => 73, 'stamina' => 75, 'attack' => 34, 'defense' => 23, 'shot' => 26, 'pass' => 24, 'dribble' => 26, 'block' => 18, 'intercept' => 20, 'tackle' => 20, 'heading' => 23, 'hand_save' => 15, 'punch_save' => 15],
                'Attaquant du Mexique Youth, aussi nommé Arbez. Membre des cinq guerriers du Soleil aztèque, Alvez privilégie les duels physiques et le jeu aérien inspiré de la lucha libre.'],
            ['', 'Lopez', 13, 'Forward', 0,
                ['speed' => 72, 'stamina' => 74, 'attack' => 33, 'defense' => 23, 'shot' => 25, 'pass' => 23, 'dribble' => 25, 'block' => 18, 'intercept' => 20, 'tackle' => 20, 'heading' => 23, 'hand_save' => 15, 'punch_save' => 15],
                'Petit attaquant du Mexique Youth, Lopez se distingue par ses frappes acrobatiques. Il forme avec Alvez et les autres guerriers du Soleil aztèque un groupe soudé autour d’Espadas.'],


            [
                'Hugo', 'Ramirez', 13, 'Midfielder', 0,
                [
                    'speed' => 70, 'stamina' => 78, 'defense' => 23, 'attack' => 30,
                    'shot' => 25, 'pass' => 27, 'dribble' => 26,
                    'block' => 18, 'intercept' => 21, 'tackle' => 21,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu offensif de la sélection le Mexique. Technique et inventif, il alimente l’attaque.',
            ],
        ];
    }
}

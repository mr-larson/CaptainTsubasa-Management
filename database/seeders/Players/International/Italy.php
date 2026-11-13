<?php

namespace Database\Seeders\Players\International;

/**
 * Stars internationales de l'univers Captain Tsubasa — noyau de l'Italie.
 * Joueurs sans contrat de club : agents libres de l'origine `captain_tsubasa`
 * et base de la sélection nationale du mode Coupe du Monde (leur nationalité est
 * dans CaptainTsubasaInternationalPlayers::nationalities()). Format d'un joueur :
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
            [
                '', 'Amoruso', 13, 'Goalkeeper', 300,
                [
                    'speed' => 57, 'stamina' => 73, 'defense' => 35, 'attack' => 14,
                    'shot' => 12, 'pass' => 16, 'dribble' => 15,
                    'block' => 24, 'intercept' => 22, 'tackle' => 18,
                    'hand_save' => 27, 'punch_save' => 26,
                ],
                'Gardien remplaçant fiable, solide doublure dans les buts italiens.',
            ],

            // --- Défenseurs (catenaccio) ---
            [
                'Salvatore', 'Gentile', 13, 'Defender', 410,
                [
                    'speed' => 69, 'stamina' => 81, 'defense' => 39, 'attack' => 21,
                    'shot' => 17, 'pass' => 22, 'dribble' => 21,
                    'block' => 30, 'intercept' => 28, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur de la Juventus, marqueur intraitable et roc du catenaccio italien.',
            ],
            [
                '', 'Tacchinardi', 13, 'Defender', 390,
                [
                    'speed' => 68, 'stamina' => 80, 'defense' => 38, 'attack' => 21,
                    'shot' => 16, 'pass' => 22, 'dribble' => 21,
                    'block' => 30, 'intercept' => 27, 'tackle' => 30,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur athlétique et combatif, précieux dans la récupération haute.',
            ],
            [
                '', 'Fresi', 13, 'Defender', 375,
                [
                    'speed' => 67, 'stamina' => 79, 'defense' => 37, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 20,
                    'block' => 29, 'intercept' => 27, 'tackle' => 29,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur central rigoureux, fort dans le jeu aérien et le placement.',
            ],
            [
                '', 'Galante', 13, 'Defender', 370,
                [
                    'speed' => 67, 'stamina' => 79, 'defense' => 37, 'attack' => 20,
                    'shot' => 16, 'pass' => 21, 'dribble' => 20,
                    'block' => 29, 'intercept' => 26, 'tackle' => 29,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Défenseur élégant et sûr, il relance proprement depuis l’arrière.',
            ],
            [
                '', 'Pagotto', 13, 'Defender', 355,
                [
                    'speed' => 66, 'stamina' => 78, 'defense' => 36, 'attack' => 19,
                    'shot' => 15, 'pass' => 20, 'dribble' => 19,
                    'block' => 28, 'intercept' => 26, 'tackle' => 28,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Latéral discipliné et endurant, fiable sur son couloir.',
            ],

            // --- Milieux ---
            [
                '', 'Totti', 13, 'Midfielder', 460,
                [
                    'speed' => 73, 'stamina' => 80, 'defense' => 27, 'attack' => 36,
                    'shot' => 28, 'pass' => 30, 'dribble' => 30,
                    'block' => 22, 'intercept' => 24, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Fantasista de la Squadra Azzurra, génie créatif aux passes et dribbles d’exception.',
                ['Forward'],
            ],
            [
                '', 'Branca', 13, 'Midfielder', 400,
                [
                    'speed' => 70, 'stamina' => 78, 'defense' => 26, 'attack' => 32,
                    'shot' => 27, 'pass' => 27, 'dribble' => 26,
                    'block' => 22, 'intercept' => 23, 'tackle' => 23,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu offensif au tir précis, redoutable arrivée de la deuxième ligne.',
            ],
            [
                '', 'Delvecchio', 13, 'Midfielder', 390,
                [
                    'speed' => 70, 'stamina' => 79, 'defense' => 28, 'attack' => 31,
                    'shot' => 26, 'pass' => 27, 'dribble' => 26,
                    'block' => 23, 'intercept' => 25, 'tackle' => 24,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu travailleur et généreux, il abat un volume de jeu considérable.',
            ],
            [
                '', 'Panucci', 13, 'Midfielder', 385,
                [
                    'speed' => 71, 'stamina' => 80, 'defense' => 30, 'attack' => 29,
                    'shot' => 23, 'pass' => 27, 'dribble' => 25,
                    'block' => 25, 'intercept' => 27, 'tackle' => 26,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Milieu défensif à vocation de récupération, charnière entre les lignes.',
            ],

            // --- Attaquants ---
            [
                '', 'Lentini', 13, 'Forward', 450,
                [
                    'speed' => 76, 'stamina' => 79, 'defense' => 23, 'attack' => 37,
                    'shot' => 28, 'pass' => 26, 'dribble' => 29,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Ailier vif et percutant, ses débordements et centres font des ravages.',
            ],
            [
                '', 'Bianchi', 13, 'Forward', 435,
                [
                    'speed' => 74, 'stamina' => 78, 'defense' => 23, 'attack' => 36,
                    'shot' => 28, 'pass' => 24, 'dribble' => 27,
                    'block' => 18, 'intercept' => 20, 'tackle' => 20,
                    'hand_save' => 15, 'punch_save' => 15,
                ],
                'Avant-centre opportuniste, buteur d’instinct dans la surface.',
            ],
        ];
    }
}

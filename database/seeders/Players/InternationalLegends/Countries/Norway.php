<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class Norway
{
    public const NATIONALITY = 'Norvège';
    public const ROSTER = [];
    public const ADDITIONS = [];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Rune', 'lastname' => 'Lange', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Erik', 'lastname' => 'Thorstvedt', 'profile' => 'keeper', 'position' => 'GB', 'stats' => ['speed' => 56, 'stamina' => 74, 'attack' => 14, 'defense' => 32, 'shot' => 11, 'pass' => 19, 'dribble' => 13, 'hand_save' => 27, 'punch_save' => 28, 'intercept' => 24]],
        ['firstname' => 'Henning', 'lastname' => 'Berg', 'profile' => 'fullback', 'position' => 'DD', 'secondary' => 'DC', 'stats' => ['speed' => 65, 'stamina' => 78, 'attack' => 20, 'defense' => 31, 'shot' => 15, 'pass' => 23, 'dribble' => 19, 'tackle' => 27, 'intercept' => 27, 'heading' => 26, 'cross' => 22]],
        ['firstname' => 'Rune', 'lastname' => 'Bratseth', 'profile' => 'libero', 'position' => 'DC', 'stats' => ['speed' => 74, 'stamina' => 77, 'attack' => 21, 'defense' => 34, 'shot' => 16, 'pass' => 24, 'dribble' => 20, 'intercept' => 29, 'tackle' => 28, 'heading' => 28]],
        ['firstname' => 'Ronny', 'lastname' => 'Johnsen', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => 'MDC', 'stats' => ['speed' => 70, 'stamina' => 76, 'attack' => 20, 'defense' => 32, 'shot' => 16, 'pass' => 23, 'dribble' => 19, 'intercept' => 27, 'tackle' => 27, 'heading' => 28]],
        ['firstname' => 'John Arne', 'lastname' => 'Riise', 'profile' => 'wingback', 'position' => 'DG', 'secondary' => 'MG', 'stats' => ['speed' => 73, 'stamina' => 81, 'attack' => 26, 'defense' => 28, 'shot' => 28, 'pass' => 23, 'dribble' => 22, 'cross' => 27, 'tackle' => 25]],
        ['firstname' => 'Erik', 'lastname' => 'Mykland', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => 'MDC', 'stats' => ['speed' => 68, 'stamina' => 80, 'attack' => 25, 'defense' => 25, 'shot' => 19, 'pass' => 27, 'dribble' => 27, 'intercept' => 25, 'tackle' => 24]],
        ['firstname' => 'Kjetil', 'lastname' => 'Rekdal', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => 'MDC', 'stats' => ['speed' => 60, 'stamina' => 78, 'attack' => 26, 'defense' => 28, 'shot' => 25, 'pass' => 26, 'dribble' => 22, 'intercept' => 27, 'tackle' => 26]],
        ['firstname' => 'Martin', 'lastname' => 'Ødegaard', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => 'MC', 'stats' => ['speed' => 68, 'stamina' => 79, 'attack' => 32, 'defense' => 22, 'shot' => 25, 'pass' => 29, 'dribble' => 28, 'intercept' => 22]],
        ['firstname' => 'Ole Gunnar', 'lastname' => 'Solskjær', 'profile' => 'striker', 'position' => 'AD', 'secondary' => 'BU', 'stats' => ['speed' => 71, 'stamina' => 73, 'attack' => 34, 'defense' => 18, 'shot' => 29, 'pass' => 24, 'dribble' => 25, 'cross' => 23, 'heading' => 24]],
        ['firstname' => 'Erling', 'lastname' => 'Haaland', 'profile' => 'power_forward', 'position' => 'BU', 'stats' => ['speed' => 79, 'stamina' => 78, 'attack' => 37, 'defense' => 17, 'shot' => 30, 'pass' => 19, 'dribble' => 23, 'heading' => 29]],
        ['firstname' => 'Tom', 'lastname' => 'Lund', 'profile' => 'second_striker', 'position' => 'AG', 'secondary' => 'AT', 'stats' => ['speed' => 72, 'stamina' => 72, 'attack' => 32, 'defense' => 18, 'shot' => 27, 'pass' => 27, 'dribble' => 29, 'heading' => 21]],
    ];

    // Compléments de la revue Norvège : seulement douze joueurs au départ, quatre noms très connus manquants.
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Tore André', 'lastname' => 'Flo', 'profile' => 'target', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 66, 'stamina' => 72, 'attack' => 31, 'shot' => 27, 'heading' => 29, 'dribble' => 21, 'pass' => 20], 'note' => 'Coupe des Coupes 1998 et FA Cup 2000 avec Chelsea, Rangers ; grand attaquant de tête et de surface.'],
        ['firstname' => 'John', 'lastname' => 'Carew', 'profile' => 'target', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 72, 'attack' => 31, 'shot' => 26, 'heading' => 31, 'dribble' => 20, 'pass' => 22], 'note' => 'Champion d’Espagne 2004 avec Valence, Lyon, Aston Villa ; avant-centre de gabarit, redoutable de la tête.'],
        ['firstname' => 'Brede', 'lastname' => 'Hangeland', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 56, 'stamina' => 74, 'defense' => 32, 'block' => 28, 'tackle' => 27, 'heading' => 30, 'pass' => 20], 'note' => 'Finaliste de la Ligue Europa 2010 avec Fulham, Crystal Palace ; défenseur central de grande taille, dominant dans les airs.'],
        ['firstname' => 'Alexander', 'lastname' => 'Sørloth', 'profile' => 'target', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 70, 'stamina' => 76, 'attack' => 31, 'shot' => 26, 'heading' => 28, 'dribble' => 22], 'note' => 'Villarreal puis Atlético de Madrid, meilleur buteur norvégien de sa génération avec Haaland ; avant-centre rapide et puissant.'],
    ];
}

<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes Allemagne : profils de jeu éditables, sans statistiques de carrière réelles. */
final class Germany
{
    public const NATIONALITY = 'Allemagne';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
        ['firstname' => 'Franz', 'lastname' => 'Beckenbauer', 'profile' => 'libero'],
        ['firstname' => 'Lothar', 'lastname' => 'Matthäus', 'profile' => 'box_to_box'],
        ['firstname' => 'Philipp', 'lastname' => 'Lahm', 'profile' => 'fullback'],
        ['firstname' => 'Andreas', 'lastname' => 'Brehme', 'profile' => 'wingback'],
        ['firstname' => 'Jürgen', 'lastname' => 'Kohler', 'profile' => 'stopper'],
        ['firstname' => 'Guido', 'lastname' => 'Buchwald', 'profile' => 'stopper'],
        ['firstname' => 'Pierre', 'lastname' => 'Littbarski', 'profile' => 'winger'],
        ['firstname' => 'Thomas', 'lastname' => 'Hässler', 'profile' => 'playmaker'],
        ['firstname' => 'Miroslav', 'lastname' => 'Klose', 'profile' => 'target'],
        ['firstname' => 'Jürgen', 'lastname' => 'Klinsmann', 'profile' => 'striker'],
        ['firstname' => 'Rudi', 'lastname' => 'Völler', 'profile' => 'striker'],
        ['firstname' => 'Lukas', 'lastname' => 'Podolski', 'profile' => 'power_forward'],
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
        ['firstname' => 'Gerd', 'lastname' => 'Müller', 'profile' => 'striker', 'world_cup_rank' => 9, 'position' => 'BU', 'secondary' => null, 'stats' => ['attack' => 36, 'shot' => 30, 'heading' => 28]],
        ['firstname' => 'Karl-Heinz', 'lastname' => 'Rummenigge', 'profile' => 'striker', 'world_cup_rank' => 34, 'position' => 'BU', 'secondary' => 'AD', 'stats' => []],
        ['firstname' => 'Thomas', 'lastname' => 'Müller', 'profile' => 'second_striker', 'world_cup_rank' => 36, 'position' => 'AT', 'secondary' => 'AD', 'stats' => []],
        ['firstname' => 'Manuel', 'lastname' => 'Neuer', 'profile' => 'keeper', 'world_cup_rank' => 37, 'position' => 'GB', 'secondary' => null, 'stats' => ['defense' => 36, 'hand_save' => 29, 'punch_save' => 28, 'speed' => 66, 'pass' => 23]],
        ['firstname' => 'Helmut', 'lastname' => 'Rahn', 'profile' => 'winger_forward', 'world_cup_rank' => 41, 'position' => 'AD', 'secondary' => 'BU', 'stats' => []],
    ];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Oliver', 'lastname' => 'Kahn', 'profile' => 'keeper', 'stats' => ['defense' => 35, 'hand_save' => 29, 'punch_save' => 28], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Matthias', 'lastname' => 'Sammer', 'profile' => 'libero', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Michael', 'lastname' => 'Ballack', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Bodo', 'lastname' => 'Illgner', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Andreas', 'lastname' => 'Möller', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Karl-Heinz', 'lastname' => 'Riedle', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
    ];

    // Compléments de la revue Allemagne : générations 1950-1990 et 2010-2020.
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Sepp', 'lastname' => 'Maier', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 58, 'stamina' => 72, 'defense' => 32, 'hand_save' => 28, 'punch_save' => 27], 'note' => 'Champion du monde 1974 et d’Europe 1972, trois Coupes d’Europe avec le Bayern ; gardien acrobatique et sûr.'],
        ['firstname' => 'Paul', 'lastname' => 'Breitner', 'profile' => 'wingback', 'position' => 'DG', 'secondary' => 'MC', 'stats' => ['speed' => 68, 'stamina' => 76, 'defense' => 27, 'attack' => 26, 'pass' => 27, 'shot' => 24], 'note' => 'Champion du monde 1974 et buteur en deux finales de Coupe du monde, Real Madrid et Bayern ; latéral offensif.'],
        ['firstname' => 'Fritz', 'lastname' => 'Walter', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 68, 'attack' => 33, 'shot' => 25, 'pass' => 30, 'dribble' => 27], 'note' => 'Capitaine champion du monde 1954 (« miracle de Berne ») avec Kaiserslautern ; meneur et passeur.'],
        ['firstname' => 'Günter', 'lastname' => 'Netzer', 'profile' => 'playmaker', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 68, 'attack' => 33, 'shot' => 27, 'pass' => 31, 'dribble' => 26], 'note' => 'Champion d’Europe 1972, Mönchengladbach puis Real Madrid ; passes longues et frappes lointaines.'],
        ['firstname' => 'Toni', 'lastname' => 'Kroos', 'profile' => 'playmaker', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 56, 'stamina' => 74, 'attack' => 28, 'defense' => 21, 'shot' => 25, 'pass' => 31, 'dribble' => 25], 'note' => 'Champion du monde 2014, six Ligues des champions avec le Real Madrid ; passeur d’une précision rare, lent mais jamais pressé.'],
        ['firstname' => 'Bastian', 'lastname' => 'Schweinsteiger', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 66, 'stamina' => 80, 'attack' => 28, 'defense' => 26, 'shot' => 25, 'pass' => 28, 'dribble' => 24, 'tackle' => 24, 'heading' => 23], 'note' => 'Champion du monde 2014, Ligue des champions 2013 ; milieu de combat et de relance.'],
        ['firstname' => 'Uwe', 'lastname' => 'Seeler', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 68, 'stamina' => 72, 'attack' => 34, 'shot' => 29, 'heading' => 27], 'note' => 'Idole de Hambourg, finaliste du Mondial 1966, quatre Coupes du monde ; avant-centre buteur et combatif.'],
    ];
}

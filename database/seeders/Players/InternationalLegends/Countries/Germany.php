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
}

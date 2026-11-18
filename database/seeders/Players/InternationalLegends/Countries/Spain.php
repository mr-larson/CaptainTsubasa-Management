<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes Espagne : profils de jeu éditables, sans statistiques de carrière réelles. */
final class Spain
{
    public const NATIONALITY = 'Espagne';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
        ['firstname' => 'Xavi', 'lastname' => 'Hernández', 'profile' => 'box_to_box', 'world_cup_rank' => 27, 'position' => 'MC', 'secondary' => 'MOC', 'stats' => ['attack' => 30, 'pass' => 30, 'dribble' => 28, 'speed' => 62, 'stamina' => 80, 'defense' => 24, 'shot' => 21, 'intercept' => 25]],
        ['firstname' => 'Andrés', 'lastname' => 'Iniesta', 'profile' => 'box_to_box', 'world_cup_rank' => 28, 'position' => 'MC', 'secondary' => 'MOC', 'stats' => ['attack' => 32, 'pass' => 29, 'dribble' => 30, 'speed' => 68, 'stamina' => 75, 'defense' => 21, 'shot' => 24]],
        ['firstname' => 'Iker', 'lastname' => 'Casillas', 'profile' => 'keeper', 'world_cup_rank' => 38, 'position' => 'GB', 'secondary' => null, 'stats' => ['defense' => 35, 'hand_save' => 29, 'punch_save' => 27, 'speed' => 62, 'stamina' => 74, 'attack' => 14, 'shot' => 11, 'pass' => 20, 'dribble' => 14, 'intercept' => 26]],
        ['firstname' => '', 'lastname' => 'Rodri', 'profile' => 'holding', 'world_cup_rank' => 48, 'position' => 'MDC', 'secondary' => 'MC', 'stats' => ['speed' => 59, 'stamina' => 81, 'attack' => 25, 'defense' => 32, 'shot' => 24, 'pass' => 29, 'dribble' => 23, 'intercept' => 29, 'tackle' => 27]],
    ];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Fernando', 'lastname' => 'Hierro', 'profile' => 'libero', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Raúl', 'lastname' => 'González', 'profile' => 'second_striker', 'stats' => ['speed' => 68, 'stamina' => 77, 'attack' => 35, 'defense' => 19, 'shot' => 28, 'pass' => 25, 'dribble' => 27, 'heading' => 25], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Pep', 'lastname' => 'Guardiola', 'profile' => 'holding', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Sergio', 'lastname' => 'Ramos', 'profile' => 'fullback', 'position' => 'DD', 'secondary' => 'DC', 'stats' => ['speed' => 71, 'stamina' => 80, 'attack' => 25, 'defense' => 33, 'shot' => 22, 'pass' => 24, 'dribble' => 22, 'tackle' => 28, 'heading' => 29, 'cross' => 25]],
        ['firstname' => 'Carles', 'lastname' => 'Puyol', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => 'DD', 'stats' => ['speed' => 65, 'stamina' => 81, 'attack' => 19, 'defense' => 35, 'shot' => 15, 'pass' => 21, 'dribble' => 17, 'block' => 29, 'tackle' => 29, 'intercept' => 28, 'heading' => 28]],
        ['firstname' => 'Gerard', 'lastname' => 'Piqué', 'profile' => 'libero', 'position' => 'DC', 'stats' => ['speed' => 59, 'stamina' => 76, 'attack' => 23, 'defense' => 33, 'shot' => 18, 'pass' => 27, 'dribble' => 22, 'intercept' => 28, 'heading' => 28, 'tackle' => 26]],
        ['firstname' => 'Jordi', 'lastname' => 'Alba', 'profile' => 'wingback', 'position' => 'DG', 'secondary' => 'MG', 'stats' => ['speed' => 79, 'stamina' => 79, 'attack' => 27, 'defense' => 26, 'shot' => 19, 'pass' => 25, 'dribble' => 25, 'cross' => 28]],
        ['firstname' => 'Lamine', 'lastname' => 'Yamal', 'profile' => 'winger_forward', 'position' => 'AD', 'stats' => ['speed' => 78, 'stamina' => 70, 'attack' => 32, 'defense' => 17, 'shot' => 26, 'pass' => 27, 'dribble' => 30, 'cross' => 27]],
        ['firstname' => 'Fernando', 'lastname' => 'Torres', 'profile' => 'striker', 'position' => 'BU', 'stats' => ['speed' => 79, 'stamina' => 75, 'attack' => 35, 'defense' => 17, 'shot' => 28, 'pass' => 21, 'dribble' => 26, 'heading' => 26]],
    ];
}

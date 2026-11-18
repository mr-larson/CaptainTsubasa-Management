<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes Croatie : profils de jeu éditables, sans statistiques de carrière réelles. */
final class Croatia
{
    public const NATIONALITY = 'Croatie';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
        ['firstname' => 'Luka', 'lastname' => 'Modrić', 'profile' => 'box_to_box', 'world_cup_rank' => 31, 'position' => 'MC', 'secondary' => 'MOC', 'stats' => ['attack' => 31, 'pass' => 29, 'dribble' => 28, 'stamina' => 81, 'speed' => 68, 'defense' => 25, 'shot' => 23, 'intercept' => 25]],
    ];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Davor', 'lastname' => 'Šuker', 'profile' => 'striker', 'stats' => ['speed' => 66, 'stamina' => 73, 'attack' => 35, 'defense' => 17, 'shot' => 29, 'pass' => 23, 'dribble' => 26, 'heading' => 25], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Vedran', 'lastname' => 'Runje', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Milan', 'lastname' => 'Rapaić', 'profile' => 'winger', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Boško', 'lastname' => 'Balaban', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Robert', 'lastname' => 'Špehar', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Mario', 'lastname' => 'Stanić', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Zvonimir', 'lastname' => 'Boban', 'profile' => 'playmaker', 'stats' => ['speed' => 65, 'stamina' => 74, 'attack' => 31, 'defense' => 22, 'shot' => 24, 'pass' => 28, 'dribble' => 28], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Stipe', 'lastname' => 'Pletikosa', 'profile' => 'keeper', 'position' => 'GB', 'stats' => ['speed' => 55, 'stamina' => 72, 'attack' => 14, 'defense' => 31, 'shot' => 11, 'pass' => 18, 'dribble' => 13, 'hand_save' => 27, 'punch_save' => 25]],
        ['firstname' => 'Darijo', 'lastname' => 'Srna', 'profile' => 'wingback', 'position' => 'DD', 'secondary' => 'MD', 'stats' => ['speed' => 72, 'stamina' => 81, 'attack' => 25, 'defense' => 28, 'shot' => 23, 'pass' => 26, 'dribble' => 24, 'cross' => 29]],
        ['firstname' => 'Robert', 'lastname' => 'Kovač', 'profile' => 'stopper', 'position' => 'DC', 'stats' => ['speed' => 59, 'stamina' => 76, 'attack' => 18, 'defense' => 32, 'shot' => 14, 'pass' => 20, 'dribble' => 16, 'tackle' => 28, 'block' => 28, 'intercept' => 26, 'heading' => 27]],
        ['firstname' => 'Dario', 'lastname' => 'Šimić', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => 'DD', 'stats' => ['speed' => 65, 'stamina' => 76, 'attack' => 19, 'defense' => 31, 'shot' => 14, 'pass' => 21, 'dribble' => 18, 'tackle' => 27, 'intercept' => 27]],
        ['firstname' => 'Robert', 'lastname' => 'Jarni', 'profile' => 'wingback', 'position' => 'DG', 'secondary' => 'MG', 'stats' => ['speed' => 77, 'stamina' => 79, 'attack' => 26, 'defense' => 26, 'shot' => 21, 'pass' => 25, 'dribble' => 25, 'cross' => 27]],
        ['firstname' => 'Ivan', 'lastname' => 'Rakitić', 'profile' => 'playmaker', 'position' => 'MD', 'secondary' => 'MC', 'stats' => ['speed' => 62, 'stamina' => 78, 'attack' => 29, 'defense' => 24, 'shot' => 26, 'pass' => 28, 'dribble' => 26, 'intercept' => 23]],
        ['firstname' => 'Robert', 'lastname' => 'Prosinečki', 'profile' => 'playmaker', 'position' => 'MG', 'secondary' => 'MOC', 'stats' => ['speed' => 61, 'stamina' => 65, 'attack' => 31, 'defense' => 18, 'shot' => 25, 'pass' => 29, 'dribble' => 29]],
        ['firstname' => 'Mario', 'lastname' => 'Mandžukić', 'profile' => 'worker_forward', 'position' => 'BU', 'secondary' => 'AG', 'stats' => ['speed' => 67, 'stamina' => 82, 'attack' => 32, 'defense' => 24, 'shot' => 27, 'pass' => 23, 'dribble' => 22, 'heading' => 29]],
    ];
}

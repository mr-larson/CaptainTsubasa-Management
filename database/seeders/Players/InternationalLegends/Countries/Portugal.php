<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes Portugal : profils de jeu éditables, sans statistiques de carrière réelles. */
final class Portugal
{
    public const NATIONALITY = 'Portugal';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
        ['firstname' => '', 'lastname' => 'Eusébio', 'profile' => 'striker', 'world_cup_rank' => 13, 'position' => 'BU', 'secondary' => 'AT', 'stats' => ['attack' => 35, 'shot' => 30, 'speed' => 78]],
    ];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Luís', 'lastname' => 'Figo', 'profile' => 'winger', 'stats' => ['attack' => 34, 'dribble' => 28, 'pass' => 28, 'cross' => 28], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Rui', 'lastname' => 'Costa', 'profile' => 'playmaker', 'stats' => ['speed' => 65, 'stamina' => 74, 'attack' => 32, 'pass' => 30, 'dribble' => 28, 'shot' => 25], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Sérgio', 'lastname' => 'Conceição', 'profile' => 'winger', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Vítor', 'lastname' => 'Baía', 'profile' => 'keeper', 'position' => 'GB', 'stats' => ['speed' => 59, 'stamina' => 74, 'attack' => 14, 'defense' => 34, 'shot' => 11, 'pass' => 22, 'dribble' => 14, 'hand_save' => 28, 'punch_save' => 27, 'intercept' => 26]],
        ['firstname' => 'João', 'lastname' => 'Pinto', 'profile' => 'fullback', 'position' => 'DD', 'stats' => ['speed' => 67, 'stamina' => 81, 'attack' => 22, 'defense' => 30, 'shot' => 16, 'pass' => 24, 'dribble' => 21, 'tackle' => 27, 'intercept' => 26, 'cross' => 25], 'note' => 'Arrière droit du FC Porto, distinct de son homonyme attaquant.'],
        ['firstname' => '', 'lastname' => 'Pepe', 'profile' => 'stopper', 'position' => 'DC', 'stats' => ['speed' => 69, 'stamina' => 80, 'attack' => 19, 'defense' => 35, 'shot' => 14, 'pass' => 22, 'dribble' => 18, 'block' => 29, 'tackle' => 29, 'intercept' => 28, 'heading' => 29]],
        ['firstname' => 'Ricardo', 'lastname' => 'Carvalho', 'profile' => 'libero', 'position' => 'DC', 'stats' => ['speed' => 66, 'stamina' => 77, 'attack' => 21, 'defense' => 34, 'shot' => 15, 'pass' => 25, 'dribble' => 21, 'intercept' => 29, 'tackle' => 28, 'heading' => 27]],
        ['firstname' => 'Fábio', 'lastname' => 'Coentrão', 'profile' => 'wingback', 'position' => 'DG', 'secondary' => 'MG', 'stats' => ['speed' => 76, 'stamina' => 78, 'attack' => 25, 'defense' => 28, 'shot' => 20, 'pass' => 25, 'dribble' => 26, 'cross' => 27, 'tackle' => 25, 'intercept' => 25]],
        ['firstname' => 'Mário', 'lastname' => 'Coluna', 'profile' => 'holding', 'position' => 'MDC', 'secondary' => 'MC', 'stats' => ['speed' => 67, 'stamina' => 82, 'attack' => 28, 'defense' => 30, 'shot' => 26, 'pass' => 27, 'dribble' => 25, 'intercept' => 27, 'tackle' => 27]],
        ['firstname' => '', 'lastname' => 'Deco', 'profile' => 'playmaker', 'position' => 'MC', 'secondary' => 'MOC', 'stats' => ['speed' => 64, 'stamina' => 76, 'attack' => 31, 'defense' => 23, 'shot' => 25, 'pass' => 29, 'dribble' => 28, 'intercept' => 23]],
        ['firstname' => 'Cristiano', 'lastname' => 'Ronaldo', 'profile' => 'winger_forward', 'position' => 'AG', 'secondary' => 'BU', 'stats' => ['speed' => 81, 'stamina' => 80, 'attack' => 38, 'defense' => 17, 'shot' => 30, 'pass' => 25, 'dribble' => 29, 'heading' => 30, 'cross' => 26]],
    ];
}

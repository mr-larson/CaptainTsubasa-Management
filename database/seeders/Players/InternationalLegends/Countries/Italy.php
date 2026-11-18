<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes Italie : profils de jeu éditables, sans statistiques de carrière réelles. */
final class Italy
{
    public const NATIONALITY = 'Italie';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
        ['firstname' => 'Fabio', 'lastname' => 'Cannavaro', 'profile' => 'stopper'],
        ['firstname' => 'Gianluca', 'lastname' => 'Zambrotta', 'profile' => 'fullback'],
        ['firstname' => 'Gennaro', 'lastname' => 'Gattuso', 'profile' => 'holding'],
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
        ['firstname' => 'Paolo', 'lastname' => 'Rossi', 'profile' => 'striker', 'world_cup_rank' => 14, 'position' => 'BU', 'secondary' => null, 'stats' => []],
        ['firstname' => 'Giuseppe', 'lastname' => 'Meazza', 'profile' => 'second_striker', 'world_cup_rank' => 19, 'position' => 'AT', 'secondary' => 'MOC', 'stats' => []],
        ['firstname' => 'Dino', 'lastname' => 'Zoff', 'profile' => 'keeper', 'world_cup_rank' => 22, 'position' => 'GB', 'secondary' => null, 'stats' => ['defense' => 35, 'hand_save' => 28, 'punch_save' => 27]],
        ['firstname' => 'Gianluigi', 'lastname' => 'Buffon', 'profile' => 'keeper', 'world_cup_rank' => 23, 'position' => 'GB', 'secondary' => null, 'stats' => ['defense' => 36, 'hand_save' => 29, 'punch_save' => 28, 'block' => 27]],
        ['firstname' => 'Paolo', 'lastname' => 'Maldini', 'profile' => 'wingback', 'world_cup_rank' => 24, 'position' => 'DG', 'secondary' => 'DC', 'stats' => ['defense' => 34, 'tackle' => 29, 'intercept' => 28, 'block' => 28]],
        ['firstname' => 'Roberto', 'lastname' => 'Baggio', 'profile' => 'second_striker', 'world_cup_rank' => 49, 'position' => 'AT', 'secondary' => 'MOC', 'stats' => []],
    ];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Alessandro', 'lastname' => 'Nesta', 'profile' => 'stopper', 'stats' => ['defense' => 33, 'tackle' => 28, 'intercept' => 28], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Alessandro', 'lastname' => 'Del Piero', 'profile' => 'second_striker', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Christian', 'lastname' => 'Vieri', 'profile' => 'power_forward', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Angelo', 'lastname' => 'Peruzzi', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Sebastiano', 'lastname' => 'Rossi', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Franco', 'lastname' => 'Baresi', 'profile' => 'libero', 'stats' => ['defense' => 34, 'intercept' => 29, 'pass' => 26], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Ciro', 'lastname' => 'Ferrara', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Christian', 'lastname' => 'Panucci', 'profile' => 'fullback', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Filippo', 'lastname' => 'Inzaghi', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
    ];
}

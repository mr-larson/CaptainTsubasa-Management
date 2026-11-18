<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes Pays-Bas : profils de jeu éditables, sans statistiques de carrière réelles. */
final class Netherlands
{
    public const NATIONALITY = 'Pays-Bas';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
        ['firstname' => 'Edwin', 'lastname' => 'van der Sar', 'profile' => 'keeper'],
        ['firstname' => 'Jaap', 'lastname' => 'Stam', 'profile' => 'stopper'],
        ['firstname' => 'Ronald', 'lastname' => 'Koeman', 'profile' => 'libero'],
        ['firstname' => 'Giovanni', 'lastname' => 'van Bronckhorst', 'profile' => 'wingback'],
        ['firstname' => 'Michael', 'lastname' => 'Reiziger', 'profile' => 'fullback'],
        ['firstname' => 'Frank', 'lastname' => 'Rijkaard', 'profile' => 'holding'],
        ['firstname' => 'Edgar', 'lastname' => 'Davids', 'profile' => 'box_to_box'],
        ['firstname' => 'Mark', 'lastname' => 'van Bommel', 'profile' => 'holding'],
        ['firstname' => 'Dirk', 'lastname' => 'Kuyt', 'profile' => 'worker_forward'],
        ['firstname' => 'Marc', 'lastname' => 'Overmars', 'profile' => 'winger_forward'],
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
        ['firstname' => 'Johan', 'lastname' => 'Cruyff', 'profile' => 'second_striker', 'world_cup_rank' => 10, 'position' => 'AT', 'secondary' => 'MOC', 'stats' => ['attack' => 36, 'pass' => 29, 'dribble' => 29, 'speed' => 76]],
    ];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Frank', 'lastname' => 'de Boer', 'profile' => 'libero', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Dennis', 'lastname' => 'Bergkamp', 'profile' => 'second_striker', 'stats' => ['attack' => 33, 'pass' => 27, 'dribble' => 27], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Clarence', 'lastname' => 'Seedorf', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Patrick', 'lastname' => 'Kluivert', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'John', 'lastname' => 'Bosman', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Foeke', 'lastname' => 'Booy', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Marco', 'lastname' => 'van Basten', 'profile' => 'target', 'stats' => ['attack' => 35, 'shot' => 29, 'heading' => 29], 'note' => 'Référence de la période des années 1990.'],
    ];
}

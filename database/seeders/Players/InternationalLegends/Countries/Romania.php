<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class Romania
{
    public const NATIONALITY = 'Roumanie';
    public const ROSTER = [];
    public const ADDITIONS = [];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Gheorghe', 'lastname' => 'Hagi', 'profile' => 'playmaker', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Tibor', 'lastname' => 'Selymes', 'profile' => 'wingback', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
    ];

    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Alin', 'lastname' => 'Stoica', 'profile' => 'playmaker', 'position' => 'MOC', 'stats' => ['speed' => 69, 'stamina' => 68, 'attack' => 28, 'defense' => 18, 'shot' => 23, 'pass' => 26, 'dribble' => 26, 'heading' => 16], 'note' => 'Milieu offensif roumain d’Anderlecht entre 1996 et 2002, créatif entre les lignes et habile dans les petits espaces.'],
    ];
}

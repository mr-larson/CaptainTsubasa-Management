<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class CzechRepublic
{
    public const NATIONALITY = 'République tchèque';
    public const ROSTER = [];
    public const ADDITIONS = [];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Pavel', 'lastname' => 'Nedvěd', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Daniel', 'lastname' => 'Zítka', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Jan', 'lastname' => 'Koller', 'profile' => 'target', 'stats' => ['heading' => 28, 'speed' => 55], 'note' => 'Référence de la période 2000–2010.'],
    ];
}

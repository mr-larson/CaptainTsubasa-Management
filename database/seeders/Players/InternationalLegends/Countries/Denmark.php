<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class Denmark
{
    public const NATIONALITY = 'Danemark';
    public const ROSTER = [];
    public const ADDITIONS = [];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Peter', 'lastname' => 'Schmeichel', 'profile' => 'keeper', 'stats' => ['defense' => 35, 'hand_save' => 28, 'punch_save' => 29, 'speed' => 58, 'stamina' => 76, 'attack' => 15, 'shot' => 12, 'pass' => 23, 'dribble' => 14, 'intercept' => 26], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Thomas', 'lastname' => 'Helveg', 'profile' => 'fullback', 'position' => 'DD', 'secondary' => 'MD', 'stats' => ['speed' => 68, 'stamina' => 79, 'attack' => 22, 'defense' => 29, 'shot' => 16, 'pass' => 24, 'dribble' => 21, 'tackle' => 26, 'cross' => 25]],
        ['firstname' => 'Morten', 'lastname' => 'Olsen', 'profile' => 'libero', 'position' => 'DC', 'secondary' => 'MDC', 'stats' => ['speed' => 60, 'stamina' => 76, 'attack' => 23, 'defense' => 33, 'shot' => 19, 'pass' => 27, 'dribble' => 22, 'intercept' => 29, 'tackle' => 26, 'heading' => 25]],
        ['firstname' => 'Daniel', 'lastname' => 'Agger', 'profile' => 'libero', 'position' => 'DC', 'stats' => ['speed' => 65, 'stamina' => 75, 'attack' => 23, 'defense' => 32, 'shot' => 22, 'pass' => 26, 'dribble' => 22, 'intercept' => 27, 'heading' => 27]],
        ['firstname' => 'Jan', 'lastname' => 'Heintze', 'profile' => 'fullback', 'position' => 'DG', 'secondary' => 'MG', 'stats' => ['speed' => 71, 'stamina' => 80, 'attack' => 23, 'defense' => 29, 'shot' => 17, 'pass' => 24, 'dribble' => 22, 'intercept' => 26, 'cross' => 26]],
        ['firstname' => 'Søren', 'lastname' => 'Lerby', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => 'MDC', 'stats' => ['speed' => 66, 'stamina' => 82, 'attack' => 29, 'defense' => 27, 'shot' => 26, 'pass' => 26, 'dribble' => 23, 'tackle' => 26]],
        ['firstname' => 'Christian', 'lastname' => 'Eriksen', 'profile' => 'playmaker', 'position' => 'MC', 'secondary' => 'MOC', 'stats' => ['speed' => 62, 'stamina' => 77, 'attack' => 30, 'defense' => 21, 'shot' => 26, 'pass' => 29, 'dribble' => 26, 'cross' => 27]],
        ['firstname' => 'Michael', 'lastname' => 'Laudrup', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => 'AT', 'stats' => ['speed' => 70, 'stamina' => 73, 'attack' => 34, 'defense' => 18, 'shot' => 24, 'pass' => 30, 'dribble' => 29]],
        ['firstname' => 'Brian', 'lastname' => 'Laudrup', 'profile' => 'winger_forward', 'position' => 'AD', 'secondary' => 'MOC', 'stats' => ['speed' => 78, 'stamina' => 73, 'attack' => 32, 'defense' => 18, 'shot' => 25, 'pass' => 26, 'dribble' => 29, 'cross' => 26]],
        ['firstname' => 'Preben', 'lastname' => 'Elkjær Larsen', 'profile' => 'power_forward', 'position' => 'BU', 'stats' => ['speed' => 74, 'stamina' => 79, 'attack' => 35, 'defense' => 19, 'shot' => 28, 'pass' => 22, 'dribble' => 26, 'heading' => 25]],
        ['firstname' => 'Allan', 'lastname' => 'Simonsen', 'profile' => 'winger_forward', 'position' => 'AG', 'secondary' => 'AT', 'stats' => ['speed' => 76, 'stamina' => 71, 'attack' => 33, 'defense' => 17, 'shot' => 27, 'pass' => 25, 'dribble' => 29, 'heading' => 18]],
    ];

    // Compléments de la revue Danemark : seulement onze joueurs au départ (un seul gardien, aucun Tsubasa).
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Kasper', 'lastname' => 'Schmeichel', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 60, 'stamina' => 72, 'defense' => 33, 'hand_save' => 28, 'punch_save' => 28], 'note' => 'Champion d’Angleterre 2016 avec Leicester, demi-finaliste de l’Euro 2020 ; gardien sûr, héritier de son père.'],
        ['firstname' => 'Simon', 'lastname' => 'Kjær', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 72, 'defense' => 32, 'block' => 27, 'tackle' => 28, 'heading' => 27, 'pass' => 24], 'note' => 'Capitaine de l’Euro 2020 (demi-finale), champion d’Italie 2022 avec Milan ; défenseur central de caractère.'],
        ['firstname' => 'Jon Dahl', 'lastname' => 'Tomasson', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 70, 'stamina' => 72, 'attack' => 31, 'shot' => 27, 'pass' => 24, 'dribble' => 24, 'heading' => 24], 'note' => 'Ligue des champions 2003 et titre 2004 avec Milan, Feyenoord, meilleur buteur danois à son retrait ; finisseur régulier.'],
        ['firstname' => 'Pierre-Emile', 'lastname' => 'Højbjerg', 'profile' => 'holding', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 82, 'attack' => 22, 'defense' => 28, 'shot' => 22, 'pass' => 26, 'tackle' => 27, 'intercept' => 26], 'note' => 'Demi-finaliste de l’Euro 2020, Bayern et Tottenham ; milieu défensif infatigable.'],
    ];
}

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

    // Compléments de la revue Pays-Bas : légendes très connues manquantes (un seul gardien au départ).
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Ruud', 'lastname' => 'Gullit', 'profile' => 'second_striker', 'position' => 'MOC', 'secondary' => 'AT', 'stats' => ['speed' => 74, 'stamina' => 78, 'attack' => 34, 'defense' => 24, 'shot' => 27, 'pass' => 28, 'dribble' => 27, 'heading' => 26], 'note' => 'Ballon d’Or 1987, capitaine champion d’Europe 1988, Milan ; joueur total, puissant, technique et polyvalent.'],
        ['firstname' => 'Johan', 'lastname' => 'Neeskens', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 68, 'stamina' => 82, 'attack' => 28, 'defense' => 26, 'shot' => 26, 'pass' => 28, 'tackle' => 25], 'note' => 'Moteur du football total, double finaliste du Mondial 1974 et 1978, Barcelone ; milieu infatigable, tireur de penalty.'],
        ['firstname' => 'Arjen', 'lastname' => 'Robben', 'profile' => 'winger', 'position' => 'AD', 'secondary' => 'MOC', 'stats' => ['speed' => 80, 'stamina' => 72, 'attack' => 34, 'shot' => 28, 'pass' => 24, 'dribble' => 30], 'note' => 'Ligue des champions 2013 avec le Bayern, finaliste du Mondial 2010 ; ailier qui repique sur son pied gauche.'],
        ['firstname' => 'Wesley', 'lastname' => 'Sneijder', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 72, 'attack' => 31, 'shot' => 28, 'pass' => 30, 'dribble' => 25], 'note' => 'Triplé 2010 avec l’Inter, finaliste du Mondial 2010 ; meneur et frappeur de loin.'],
        ['firstname' => 'Robin', 'lastname' => 'van Persie', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 68, 'stamina' => 68, 'attack' => 34, 'shot' => 30, 'pass' => 24, 'dribble' => 26, 'heading' => 20], 'note' => 'Soulier d’or de Premier League 2013 avec Manchester United, finaliste du Mondial 2010 ; finisseur du pied gauche, volée légendaire.'],
        ['firstname' => 'Virgil', 'lastname' => 'van Dijk', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 70, 'stamina' => 76, 'defense' => 34, 'block' => 28, 'tackle' => 28, 'heading' => 29, 'pass' => 24], 'note' => 'Ligue des champions 2019 et Premier League avec Liverpool, capitaine des Oranje ; défenseur central dominant.'],
        ['firstname' => 'Hans', 'lastname' => 'van Breukelen', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 58, 'stamina' => 70, 'defense' => 31, 'hand_save' => 27, 'punch_save' => 27], 'note' => 'Champion d’Europe 1988, Coupe d’Europe 1988 avec le PSV ; gardien spécialiste des penalties.'],
        ['firstname' => 'Ronald', 'lastname' => 'de Boer', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => 'MD', 'stats' => ['speed' => 66, 'stamina' => 74, 'attack' => 28, 'shot' => 25, 'pass' => 28, 'dribble' => 25], 'note' => 'Ajax (Ligue des champions 1995), Barcelone, Rangers, 67 sélections, demi-finaliste des Mondiaux 1998 ; milieu offensif technique, passeur et buteur.'],
        ['firstname' => 'Philippe', 'lastname' => 'Cocu', 'profile' => 'fullback', 'position' => 'DD', 'secondary' => 'MC', 'stats' => ['speed' => 68, 'stamina' => 80, 'defense' => 28, 'tackle' => 26, 'pass' => 24, 'intercept' => 25], 'note' => 'PSV puis Barcelone, 101 sélections, demi-finaliste des Mondiaux 1998 et de l’Euro 2000 ; latéral droit polyvalent, infatigable.'],
    ];
}

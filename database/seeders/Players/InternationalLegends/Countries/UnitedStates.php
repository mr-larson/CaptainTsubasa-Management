<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class UnitedStates
{
    public const NATIONALITY = 'États-Unis';
    public const ROSTER = [];
    public const ADDITIONS = [];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Oguchi', 'lastname' => 'Onyewu', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
    ];

    // Onze historique des États-Unis, de niveau moyen (OVR 23 à 26), ajouté autour d'Onyewu.
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Tim', 'lastname' => 'Howard', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 58, 'stamina' => 72, 'attack' => 12, 'defense' => 30, 'shot' => 10, 'pass' => 18, 'dribble' => 12, 'block' => 20, 'intercept' => 22, 'tackle' => 15, 'heading' => 14, 'hand_save' => 28, 'punch_save' => 27], 'note' => 'Gardien de référence du pays (Everton, Manchester United), 121 sélections ; célèbre pour ses 16 arrêts contre la Belgique au Mondial 2014.'],
        ['firstname' => 'Steve', 'lastname' => 'Cherundolo', 'profile' => 'fullback', 'position' => 'DD', 'secondary' => null, 'stats' => ['speed' => 70, 'stamina' => 78, 'attack' => 17, 'defense' => 24, 'shot' => 14, 'pass' => 20, 'dribble' => 19, 'block' => 18, 'intercept' => 22, 'tackle' => 23, 'heading' => 14, 'hand_save' => 10, 'punch_save' => 10], 'note' => 'Capitaine d’Hanovre, 87 sélections ; latéral droit régulier, endurant et très fiable.'],
        ['firstname' => 'Marcelo', 'lastname' => 'Balboa', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 60, 'stamina' => 70, 'attack' => 13, 'defense' => 26, 'shot' => 11, 'pass' => 16, 'dribble' => 13, 'block' => 22, 'intercept' => 24, 'tackle' => 25, 'heading' => 25, 'hand_save' => 10, 'punch_save' => 10], 'note' => 'Quart de finale de la Copa América et deux Coupes du monde (1990, 1994), 128 sélections ; défenseur central au gabarit et au jeu de tête solides.'],
        ['firstname' => 'Eddie', 'lastname' => 'Pope', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 66, 'stamina' => 70, 'attack' => 13, 'defense' => 25, 'shot' => 11, 'pass' => 15, 'dribble' => 13, 'block' => 21, 'intercept' => 24, 'tackle' => 24, 'heading' => 24, 'hand_save' => 10, 'punch_save' => 10], 'note' => 'Mondiaux 1998, 2002 (quart de finale) et 2006, D.C. United ; défenseur central rapide et propre.'],
        ['firstname' => 'Carlos', 'lastname' => 'Bocanegra', 'profile' => 'stopper', 'position' => 'DG', 'secondary' => 'DC', 'stats' => ['speed' => 62, 'stamina' => 72, 'attack' => 13, 'defense' => 25, 'shot' => 12, 'pass' => 16, 'dribble' => 13, 'block' => 21, 'intercept' => 23, 'tackle' => 24, 'heading' => 25, 'hand_save' => 10, 'punch_save' => 10], 'note' => 'Capitaine de la sélection, Fulham puis Rangers, 110 sélections ; défenseur gaucher puissant.'],
        ['firstname' => 'Claudio', 'lastname' => 'Reyna', 'profile' => 'playmaker', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 72, 'attack' => 19, 'defense' => 18, 'shot' => 17, 'pass' => 26, 'dribble' => 24, 'block' => 14, 'intercept' => 20, 'tackle' => 18, 'heading' => 13, 'hand_save' => 10, 'punch_save' => 10], 'note' => 'Capitaine, Mondiaux 1998, 2002 et 2006, Glasgow Rangers, Sunderland et Manchester City ; milieu technique, meilleur joueur du Mondial 2002 selon la FIFA.'],
        ['firstname' => 'Michael', 'lastname' => 'Bradley', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 80, 'attack' => 20, 'defense' => 19, 'shot' => 19, 'pass' => 24, 'dribble' => 19, 'block' => 16, 'intercept' => 22, 'tackle' => 22, 'heading' => 15, 'hand_save' => 10, 'punch_save' => 10], 'note' => 'Mondiaux 2010, 2014 et 2022, 151 sélections, Toronto FC ; milieu infatigable, relayeur et capitaine.'],
        ['firstname' => 'Landon', 'lastname' => 'Donovan', 'profile' => 'winger_forward', 'position' => 'AD', 'secondary' => 'MOC', 'stats' => ['speed' => 76, 'stamina' => 72, 'attack' => 26, 'defense' => 13, 'shot' => 22, 'pass' => 22, 'dribble' => 25, 'block' => 10, 'intercept' => 14, 'tackle' => 12, 'heading' => 13, 'hand_save' => 10, 'punch_save' => 10], 'note' => 'Meilleur buteur de l’histoire du pays (57 buts), Los Angeles Galaxy et Bayer Leverkusen ; ailier rapide, buteur du Mondial 2010.'],
        ['firstname' => 'Clint', 'lastname' => 'Dempsey', 'profile' => 'second_striker', 'position' => 'AT', 'secondary' => null, 'stats' => ['speed' => 66, 'stamina' => 70, 'attack' => 28, 'defense' => 12, 'shot' => 25, 'pass' => 20, 'dribble' => 25, 'block' => 9, 'intercept' => 13, 'tackle' => 11, 'heading' => 19, 'hand_save' => 10, 'punch_save' => 10], 'note' => '57 buts en sélection, Fulham et Tottenham, Mondiaux 2006, 2010 et 2014 ; attaquant créatif et opportuniste.'],
        ['firstname' => 'Eric', 'lastname' => 'Wynalda', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 68, 'stamina' => 68, 'attack' => 27, 'defense' => 10, 'shot' => 25, 'pass' => 15, 'dribble' => 19, 'block' => 8, 'intercept' => 11, 'tackle' => 9, 'heading' => 17, 'hand_save' => 10, 'punch_save' => 10], 'note' => 'Premier buteur américain en Bundesliga, trois Coupes du monde (1990, 1994, 1998) ; avant-centre opportuniste.'],
        ['firstname' => 'Brian', 'lastname' => 'McBride', 'profile' => 'target', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 58, 'stamina' => 70, 'attack' => 26, 'defense' => 11, 'shot' => 23, 'pass' => 15, 'dribble' => 15, 'block' => 9, 'intercept' => 12, 'tackle' => 11, 'heading' => 28, 'hand_save' => 10, 'punch_save' => 10], 'note' => 'Fulham et Everton, Mondiaux 1998, 2002 et 2006 ; avant-centre dominateur de la tête, buteur du quart de finale 2002.'],
    ];
}

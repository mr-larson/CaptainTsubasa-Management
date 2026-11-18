<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes Argentine : profils de jeu éditables, sans statistiques de carrière réelles. */
final class Argentina
{
    public const NATIONALITY = 'Argentine';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
        ['firstname' => 'Ubaldo', 'lastname' => 'Fillol', 'profile' => 'keeper'],
        ['firstname' => 'Nery', 'lastname' => 'Pumpido', 'profile' => 'keeper'],
        ['firstname' => 'Daniel', 'lastname' => 'Passarella', 'profile' => 'libero'],
        ['firstname' => 'Oscar', 'lastname' => 'Ruggeri', 'profile' => 'stopper'],
        ['firstname' => 'Roberto', 'lastname' => 'Ayala', 'profile' => 'stopper'],
        ['firstname' => 'Javier', 'lastname' => 'Zanetti', 'profile' => 'fullback'],
        ['firstname' => 'Juan Pablo', 'lastname' => 'Sorín', 'profile' => 'wingback'],
        ['firstname' => 'Javier', 'lastname' => 'Mascherano', 'profile' => 'holding'],
        ['firstname' => 'Jorge', 'lastname' => 'Burruchaga', 'profile' => 'box_to_box'],
        ['firstname' => 'Diego', 'lastname' => 'Simeone', 'profile' => 'holding'],
        ['firstname' => 'Ricardo', 'lastname' => 'Giusti', 'profile' => 'box_to_box'],
        ['firstname' => 'Claudio', 'lastname' => 'Caniggia', 'profile' => 'winger_forward'],
        ['firstname' => 'Jorge', 'lastname' => 'Valdano', 'profile' => 'target'],
        ['firstname' => 'Gabriel', 'lastname' => 'Batistuta', 'profile' => 'power_forward'],
        ['firstname' => 'Martín', 'lastname' => 'Palermo', 'profile' => 'target'],
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
        ['firstname' => 'Diego', 'lastname' => 'Maradona', 'profile' => 'playmaker', 'world_cup_rank' => 2, 'position' => 'MOC', 'secondary' => 'AT', 'stats' => ['attack' => 38, 'pass' => 30, 'dribble' => 31, 'shot' => 28, 'speed' => 76]],
        ['firstname' => 'Lionel', 'lastname' => 'Messi', 'profile' => 'winger_forward', 'world_cup_rank' => 3, 'position' => 'AD', 'secondary' => 'MOC', 'stats' => ['attack' => 38, 'dribble' => 31, 'pass' => 29, 'shot' => 30, 'speed' => 77]],
        ['firstname' => 'Mario', 'lastname' => 'Kempes', 'profile' => 'striker', 'world_cup_rank' => 29, 'position' => 'BU', 'secondary' => 'AT', 'stats' => []],
    ];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Juan Sebastián', 'lastname' => 'Verón', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Lucas', 'lastname' => 'Biglia', 'profile' => 'holding', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Nicolas', 'lastname' => 'Frutos', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Fernando', 'lastname' => 'Redondo', 'profile' => 'holding', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
    ];
}

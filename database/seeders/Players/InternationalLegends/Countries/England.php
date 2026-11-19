<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes Angleterre : profils de jeu éditables, sans statistiques de carrière réelles. */
final class England
{
    public const NATIONALITY = 'Angleterre';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
        ['firstname' => 'Bobby', 'lastname' => 'Charlton', 'profile' => 'playmaker', 'world_cup_rank' => 15, 'position' => 'MOC', 'secondary' => 'MC', 'stats' => ['speed' => 68, 'stamina' => 77, 'attack' => 35, 'defense' => 20, 'shot' => 29, 'pass' => 28, 'dribble' => 27]],
        ['firstname' => 'Bobby', 'lastname' => 'Moore', 'profile' => 'stopper', 'world_cup_rank' => 21, 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 59, 'stamina' => 77, 'attack' => 20, 'defense' => 35, 'shot' => 15, 'pass' => 25, 'dribble' => 20, 'intercept' => 29, 'tackle' => 28, 'heading' => 25]],
    ];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'David', 'lastname' => 'Beckham', 'profile' => 'playmaker', 'stats' => ['pass' => 29, 'cross' => 30, 'shot' => 27, 'speed' => 63, 'stamina' => 79, 'attack' => 30, 'defense' => 23, 'dribble' => 23], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Michael', 'lastname' => 'Owen', 'profile' => 'striker', 'stats' => ['attack' => 33, 'speed' => 79, 'shot' => 27], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Alan', 'lastname' => 'Shearer', 'profile' => 'power_forward', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Gary', 'lastname' => 'Neville', 'profile' => 'fullback', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Paul', 'lastname' => 'Scholes', 'profile' => 'playmaker', 'stats' => ['speed' => 61, 'stamina' => 75, 'attack' => 31, 'defense' => 22, 'shot' => 27, 'pass' => 29, 'dribble' => 26], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Andy', 'lastname' => 'Cole', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Gordon', 'lastname' => 'Banks', 'profile' => 'keeper', 'position' => 'GB', 'stats' => ['speed' => 59, 'stamina' => 74, 'attack' => 14, 'defense' => 35, 'shot' => 11, 'pass' => 19, 'dribble' => 13, 'hand_save' => 29, 'punch_save' => 27, 'intercept' => 25]],
        ['firstname' => 'Rio', 'lastname' => 'Ferdinand', 'profile' => 'libero', 'position' => 'DC', 'stats' => ['speed' => 68, 'stamina' => 76, 'attack' => 21, 'defense' => 34, 'shot' => 16, 'pass' => 25, 'dribble' => 21, 'intercept' => 28, 'tackle' => 27, 'heading' => 27]],
        ['firstname' => 'Ashley', 'lastname' => 'Cole', 'profile' => 'fullback', 'position' => 'DG', 'stats' => ['speed' => 76, 'stamina' => 80, 'attack' => 24, 'defense' => 32, 'shot' => 17, 'pass' => 24, 'dribble' => 24, 'tackle' => 28, 'intercept' => 27, 'cross' => 25]],
        ['firstname' => 'Billy', 'lastname' => 'Wright', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => 'MDC', 'stats' => ['speed' => 59, 'stamina' => 79, 'attack' => 18, 'defense' => 33, 'shot' => 14, 'pass' => 22, 'dribble' => 17, 'intercept' => 27, 'tackle' => 28, 'heading' => 25]],
        ['firstname' => 'Steven', 'lastname' => 'Gerrard', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => 'MOC', 'stats' => ['speed' => 69, 'stamina' => 82, 'attack' => 32, 'defense' => 27, 'shot' => 28, 'pass' => 27, 'dribble' => 25, 'tackle' => 25]],
        ['firstname' => 'Geoff', 'lastname' => 'Hurst', 'profile' => 'power_forward', 'position' => 'BU', 'stats' => ['speed' => 64, 'stamina' => 76, 'attack' => 33, 'defense' => 18, 'shot' => 28, 'pass' => 20, 'dribble' => 23, 'heading' => 28]],
        ['firstname' => 'Harry', 'lastname' => 'Kane', 'profile' => 'striker', 'position' => 'BU', 'secondary' => 'AT', 'stats' => ['speed' => 61, 'stamina' => 77, 'attack' => 36, 'defense' => 19, 'shot' => 29, 'pass' => 27, 'dribble' => 24, 'heading' => 27]],
    ];

    // Compléments de la revue Angleterre : second gardien et milieux très connus manquants.
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Peter', 'lastname' => 'Shilton', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 58, 'stamina' => 74, 'defense' => 34, 'hand_save' => 28, 'punch_save' => 27], 'note' => '125 sélections, deux Coupes d’Europe avec Nottingham Forest ; gardien d’une longévité record, très bon dans les airs.'],
        ['firstname' => 'John', 'lastname' => 'Terry', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 60, 'stamina' => 74, 'defense' => 33, 'block' => 29, 'tackle' => 27, 'heading' => 29, 'pass' => 20], 'note' => 'Capitaine de Chelsea, Ligue des champions 2012 et cinq Premier League ; défenseur central, sacrifice et jeu de tête.'],
        ['firstname' => 'Frank', 'lastname' => 'Lampard', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 80, 'attack' => 31, 'defense' => 22, 'shot' => 28, 'pass' => 27, 'dribble' => 22], 'note' => 'Ligue des champions 2012 avec Chelsea, trois Premier League ; milieu arrivant de loin, frappeur de loin et buteur régulier.'],
        ['firstname' => 'Paul', 'lastname' => 'Gascoigne', 'profile' => 'playmaker', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 66, 'stamina' => 66, 'attack' => 31, 'defense' => 18, 'shot' => 25, 'pass' => 28, 'dribble' => 30], 'note' => 'Demi-finaliste de l’Italie 90 avec les Three Lions, Rangers et Lazio ; milieu génial, dribble et vision.'],
        ['firstname' => 'Jude', 'lastname' => 'Bellingham', 'profile' => 'box_to_box', 'position' => 'MOC', 'secondary' => 'MC', 'stats' => ['speed' => 70, 'stamina' => 78, 'attack' => 32, 'defense' => 24, 'shot' => 26, 'pass' => 27, 'dribble' => 28, 'tackle' => 22, 'heading' => 25], 'note' => 'Real Madrid, Golden Boy 2023 et finaliste de l’Euro 2024 ; milieu complet, arrivées dans la surface et dribbles.'],
        ['firstname' => 'Wayne', 'lastname' => 'Rooney', 'profile' => 'second_striker', 'position' => 'AT', 'secondary' => 'MC', 'stats' => ['speed' => 70, 'stamina' => 78, 'attack' => 36, 'shot' => 29, 'pass' => 27, 'dribble' => 26, 'heading' => 25], 'note' => 'Ligue des champions 2008 et cinq Premier League avec Manchester United, meilleur buteur de l’histoire des clubs et des Three Lions à son retrait ; attaquant complet et combatif.'],
        ['firstname' => 'Gary', 'lastname' => 'Lineker', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 72, 'attack' => 34, 'shot' => 29, 'dribble' => 22, 'heading' => 20], 'note' => 'Soulier d’or du Mondial 1986, Barcelone et Tottenham ; finisseur de surface rapide et opportuniste.'],
    ];
}

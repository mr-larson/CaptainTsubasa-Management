<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes Brésil : profils de jeu éditables, sans statistiques de carrière réelles. */
final class Brazil
{
    public const NATIONALITY = 'Brésil';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
        ['firstname' => 'Cláudio', 'lastname' => 'Taffarel', 'profile' => 'keeper'],
        ['firstname' => 'Carlos', 'lastname' => 'Dunga', 'profile' => 'holding'],
        ['firstname' => 'Marcos', 'lastname' => 'Cafu', 'profile' => 'wingback'],
        ['firstname' => 'Roberto', 'lastname' => 'Carlos', 'profile' => 'wingback'],
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
        ['firstname' => '', 'lastname' => 'Pelé', 'profile' => 'second_striker', 'world_cup_rank' => 1, 'position' => 'AT', 'secondary' => 'BU', 'stats' => ['attack' => 38, 'shot' => 30, 'pass' => 28, 'dribble' => 29, 'speed' => 77, 'stamina' => 78, 'heading' => 27]],
        ['firstname' => '', 'lastname' => 'Ronaldo', 'profile' => 'striker', 'world_cup_rank' => 4, 'position' => 'BU', 'secondary' => 'AT', 'stats' => ['attack' => 38, 'shot' => 30, 'dribble' => 29, 'speed' => 80]],
        ['firstname' => '', 'lastname' => 'Garrincha', 'profile' => 'winger_forward', 'world_cup_rank' => 7, 'position' => 'AD', 'secondary' => null, 'stats' => ['attack' => 35, 'dribble' => 30, 'speed' => 79, 'pass' => 26]],
        ['firstname' => '', 'lastname' => 'Didi', 'profile' => 'box_to_box', 'world_cup_rank' => 17, 'position' => 'MC', 'secondary' => 'MOC', 'stats' => []],
        ['firstname' => '', 'lastname' => 'Jairzinho', 'profile' => 'winger_forward', 'world_cup_rank' => 18, 'position' => 'AD', 'secondary' => 'BU', 'stats' => []],
        ['firstname' => '', 'lastname' => 'Romário', 'profile' => 'striker', 'world_cup_rank' => 32, 'position' => 'BU', 'secondary' => 'AT', 'stats' => ['attack' => 35, 'shot' => 29, 'dribble' => 27]],
        ['firstname' => '', 'lastname' => 'Vavá', 'profile' => 'striker', 'world_cup_rank' => 33, 'position' => 'BU', 'secondary' => null, 'stats' => []],
        ['firstname' => '', 'lastname' => 'Leônidas', 'profile' => 'striker', 'world_cup_rank' => 44, 'position' => 'BU', 'secondary' => 'AT', 'stats' => []],
    ];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => '', 'lastname' => 'Rivaldo', 'profile' => 'playmaker', 'stats' => ['attack' => 35, 'shot' => 28, 'dribble' => 28, 'pass' => 27], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => '', 'lastname' => 'Dante', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
    ];

    // Ajouts ultérieurs, assemblés en fin de catalogue pour préserver les anciens ids du seeder.
    public const EXTRA_ADDITIONS = [
        ['firstname' => '', 'lastname' => 'Ronaldinho', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => 'AG', 'stats' => ['speed' => 77, 'stamina' => 73, 'attack' => 36, 'defense' => 18, 'shot' => 28, 'pass' => 31, 'dribble' => 31, 'heading' => 18], 'note' => 'Créateur brésilien, spécialiste de la dernière passe, des feintes et de l’improvisation ; moins puissant à la finition que les grands buteurs.'],
        ['firstname' => '', 'lastname' => 'Neymar', 'profile' => 'winger_forward', 'position' => 'AG', 'secondary' => 'MOC', 'stats' => ['speed' => 80, 'stamina' => 74, 'attack' => 36, 'defense' => 16, 'shot' => 28, 'pass' => 28, 'dribble' => 30, 'heading' => 17], 'note' => 'Attaquant créatif et rapide, spécialiste des duels et des combinaisons ; impact défensif et jeu aérien limités.'],
    ];
}

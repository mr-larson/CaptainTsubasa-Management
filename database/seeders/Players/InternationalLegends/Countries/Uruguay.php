<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes Uruguay : profils de jeu éditables, sans statistiques de carrière réelles. */
final class Uruguay
{
    public const NATIONALITY = 'Uruguay';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
        ['firstname' => 'Fernando', 'lastname' => 'Muslera', 'profile' => 'keeper'],
        ['firstname' => 'Diego', 'lastname' => 'Godín', 'profile' => 'stopper'],
        ['firstname' => 'Diego', 'lastname' => 'Lugano', 'profile' => 'stopper'],
        ['firstname' => 'Egidio', 'lastname' => 'Arévalo Ríos', 'profile' => 'holding'],
        ['firstname' => 'Diego', 'lastname' => 'Pérez', 'profile' => 'holding'],
        ['firstname' => 'Luis', 'lastname' => 'Suárez', 'profile' => 'striker'],
        ['firstname' => 'Diego', 'lastname' => 'Forlán', 'profile' => 'second_striker'],
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
        ['firstname' => 'Juan Alberto', 'lastname' => 'Schiaffino', 'profile' => 'second_striker', 'world_cup_rank' => 20, 'position' => 'AT', 'secondary' => 'MOC', 'stats' => []],
        ['firstname' => 'José', 'lastname' => 'Nasazzi', 'profile' => 'stopper', 'world_cup_rank' => 45, 'position' => 'DC', 'secondary' => 'DD', 'stats' => []],
    ];
    public const HISTORICAL_ADDITIONS = [];

    // Compléments de la revue Uruguay : seulement neuf joueurs au départ (un seul gardien).
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Enzo', 'lastname' => 'Francescoli', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => 'AT', 'stats' => ['speed' => 64, 'stamina' => 68, 'attack' => 34, 'defense' => 18, 'shot' => 27, 'pass' => 29, 'dribble' => 29], 'note' => 'Le Prince, deux Copa América (1983, 1987), River Plate et Marseille, idole de Zidane ; meneur élégant à l’intelligence de jeu rare.'],
        ['firstname' => 'Edinson', 'lastname' => 'Cavani', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 72, 'stamina' => 82, 'attack' => 34, 'defense' => 20, 'shot' => 29, 'pass' => 21, 'dribble' => 24, 'heading' => 28], 'note' => 'Meilleur buteur de l’histoire du PSG et de l’Uruguay, Napoli, Copa América 2011 ; avant-centre infatigable, pressing et jeu de tête.'],
        ['firstname' => 'Obdulio', 'lastname' => 'Varela', 'profile' => 'holding', 'position' => 'MC', 'secondary' => 'DC', 'stats' => ['speed' => 58, 'stamina' => 76, 'attack' => 22, 'defense' => 31, 'pass' => 24, 'intercept' => 27, 'tackle' => 28, 'heading' => 25], 'note' => 'Capitaine du Maracanazo, champion du monde 1950, Peñarol ; le Negro Jefe, meneur d’hommes et guerrier.'],
        ['firstname' => 'Alcides', 'lastname' => 'Ghiggia', 'profile' => 'winger', 'position' => 'AD', 'secondary' => null, 'stats' => ['speed' => 76, 'stamina' => 70, 'attack' => 32, 'shot' => 27, 'pass' => 24, 'dribble' => 28], 'note' => 'Auteur du but de la victoire en finale du Mondial 1950 au Maracanã ; ailier droit rapide, réduit au silence tout un stade.'],
        ['firstname' => 'Federico', 'lastname' => 'Valverde', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => 'AD', 'stats' => ['speed' => 78, 'stamina' => 82, 'attack' => 28, 'defense' => 25, 'shot' => 26, 'pass' => 27, 'dribble' => 25, 'tackle' => 24], 'note' => 'Cinq Ligues des champions avec le Real Madrid ; milieu à moteur de course et frappe lointaine, le Faucon.'],
        ['firstname' => 'Ladislao', 'lastname' => 'Mazurkiewicz', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 58, 'stamina' => 72, 'defense' => 33, 'hand_save' => 27, 'punch_save' => 27], 'note' => 'Demi-finaliste du Mondial 1970, Peñarol et Atlético Mineiro ; gardien célèbre pour avoir été dribblé par Pelé sans but.'],
        ['firstname' => 'Paolo', 'lastname' => 'Montero', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 60, 'stamina' => 72, 'defense' => 32, 'block' => 27, 'tackle' => 28, 'heading' => 27, 'pass' => 20], 'note' => 'Juventus, finaliste de la Ligue des champions ; défenseur dur et vif sur l’homme.'],
        ['firstname' => 'Álvaro', 'lastname' => 'Recoba', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => null, 'stats' => ['speed' => 64, 'stamina' => 62, 'attack' => 31, 'defense' => 14, 'shot' => 29, 'pass' => 26, 'dribble' => 27], 'note' => 'L’Inter des années 2000, le Chino ; gaucher virtuose, frappes de loin et coups francs, peu défensif.'],
    ];
}

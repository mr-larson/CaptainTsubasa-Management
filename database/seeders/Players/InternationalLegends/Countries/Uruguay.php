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
}

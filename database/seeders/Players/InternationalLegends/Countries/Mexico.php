<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes Mexique : profils de jeu éditables, sans statistiques de carrière réelles. */
final class Mexico
{
    public const NATIONALITY = 'Mexique';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
        ['firstname' => 'Guillermo', 'lastname' => 'Ochoa', 'profile' => 'keeper'],
        ['firstname' => 'Rafael', 'lastname' => 'Márquez', 'profile' => 'libero'],
        ['firstname' => 'Andrés', 'lastname' => 'Guardado', 'profile' => 'box_to_box'],
        ['firstname' => 'Carlos', 'lastname' => 'Salcido', 'profile' => 'fullback'],
        ['firstname' => 'Gerardo', 'lastname' => 'Torrado', 'profile' => 'holding'],
        ['firstname' => 'Cuauhtémoc', 'lastname' => 'Blanco', 'profile' => 'second_striker'],
        ['firstname' => 'Luis', 'lastname' => 'Hernández', 'profile' => 'striker'],
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
    ];
    public const HISTORICAL_ADDITIONS = [];
}

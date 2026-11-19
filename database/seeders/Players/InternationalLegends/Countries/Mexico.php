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

    // Compléments de la revue Mexique : seulement sept joueurs au départ (un gardien, deux défenseurs).
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Hugo', 'lastname' => 'Sánchez', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 70, 'stamina' => 68, 'attack' => 35, 'shot' => 30, 'pass' => 20, 'dribble' => 25, 'heading' => 24], 'note' => 'Cinq fois meilleur buteur du championnat d’Espagne avec le Real Madrid, célèbre pour ses ciseaux acrobatiques ; finisseur instinctif.'],
        ['firstname' => 'Javier', 'lastname' => 'Hernández', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 74, 'stamina' => 72, 'attack' => 33, 'shot' => 28, 'pass' => 20, 'dribble' => 24, 'heading' => 25], 'note' => 'Chicharito, deux Premier League avec Manchester United, meilleur buteur de l’histoire du Mexique ; renard de surface, appels dans le dos.'],
        ['firstname' => 'Jorge', 'lastname' => 'Campos', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 72, 'defense' => 30, 'hand_save' => 26, 'punch_save' => 27, 'pass' => 20], 'note' => 'Gardien flamboyant des Mondiaux 1994 et 1998, parfois avant-centre en club ; vif, imprévisible, sorties hors de sa surface.'],
        ['firstname' => 'Claudio', 'lastname' => 'Suárez', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 60, 'stamina' => 76, 'defense' => 31, 'block' => 27, 'tackle' => 27, 'heading' => 27, 'pass' => 22], 'note' => 'Recordman de sélections du Mexique (plus de 170), Pumas et Chivas ; défenseur central solide et régulier.'],
        ['firstname' => 'Pável', 'lastname' => 'Pardo', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 78, 'attack' => 24, 'defense' => 25, 'shot' => 22, 'pass' => 27, 'tackle' => 23], 'note' => 'Champion d’Allemagne 2007 avec Stuttgart, plusieurs Mondiaux ; milieu complet et régulier.'],
        ['firstname' => 'Hirving', 'lastname' => 'Lozano', 'profile' => 'winger', 'position' => 'AG', 'secondary' => null, 'stats' => ['speed' => 80, 'stamina' => 74, 'attack' => 30, 'shot' => 25, 'pass' => 22, 'dribble' => 27], 'note' => 'Auteur du but contre l’Allemagne au Mondial 2018, champion d’Italie 2023 avec Naples ; ailier explosif.'],
        ['firstname' => 'Raúl', 'lastname' => 'Jiménez', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 68, 'stamina' => 76, 'attack' => 31, 'shot' => 26, 'pass' => 22, 'dribble' => 22, 'heading' => 25], 'note' => 'Benfica, Wolverhampton, Fulham ; avant-centre travailleur et physique, jeu de dos.'],
    ];
}

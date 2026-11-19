<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes Italie : profils de jeu éditables, sans statistiques de carrière réelles. */
final class Italy
{
    public const NATIONALITY = 'Italie';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
        ['firstname' => 'Fabio', 'lastname' => 'Cannavaro', 'profile' => 'stopper'],
        ['firstname' => 'Gianluca', 'lastname' => 'Zambrotta', 'profile' => 'fullback'],
        ['firstname' => 'Gennaro', 'lastname' => 'Gattuso', 'profile' => 'holding'],
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
        ['firstname' => 'Paolo', 'lastname' => 'Rossi', 'profile' => 'striker', 'world_cup_rank' => 14, 'position' => 'BU', 'secondary' => null, 'stats' => []],
        ['firstname' => 'Giuseppe', 'lastname' => 'Meazza', 'profile' => 'second_striker', 'world_cup_rank' => 19, 'position' => 'AT', 'secondary' => 'MOC', 'stats' => []],
        ['firstname' => 'Dino', 'lastname' => 'Zoff', 'profile' => 'keeper', 'world_cup_rank' => 22, 'position' => 'GB', 'secondary' => null, 'stats' => ['defense' => 35, 'hand_save' => 28, 'punch_save' => 27]],
        ['firstname' => 'Gianluigi', 'lastname' => 'Buffon', 'profile' => 'keeper', 'world_cup_rank' => 23, 'position' => 'GB', 'secondary' => null, 'stats' => ['defense' => 36, 'hand_save' => 29, 'punch_save' => 28, 'block' => 27]],
        ['firstname' => 'Paolo', 'lastname' => 'Maldini', 'profile' => 'wingback', 'world_cup_rank' => 24, 'position' => 'DG', 'secondary' => 'DC', 'stats' => ['defense' => 34, 'tackle' => 29, 'intercept' => 28, 'block' => 28]],
        ['firstname' => 'Roberto', 'lastname' => 'Baggio', 'profile' => 'second_striker', 'world_cup_rank' => 49, 'position' => 'AT', 'secondary' => 'MOC', 'stats' => []],
    ];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Alessandro', 'lastname' => 'Nesta', 'profile' => 'stopper', 'stats' => ['defense' => 33, 'tackle' => 28, 'intercept' => 28], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Alessandro', 'lastname' => 'Del Piero', 'profile' => 'second_striker', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Christian', 'lastname' => 'Vieri', 'profile' => 'power_forward', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Angelo', 'lastname' => 'Peruzzi', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Sebastiano', 'lastname' => 'Rossi', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Franco', 'lastname' => 'Baresi', 'profile' => 'libero', 'stats' => ['defense' => 34, 'intercept' => 29, 'pass' => 26], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Ciro', 'lastname' => 'Ferrara', 'profile' => 'stopper', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Christian', 'lastname' => 'Panucci', 'profile' => 'fullback', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Filippo', 'lastname' => 'Inzaghi', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
    ];

    // Compléments de la revue Italie : milieux (un seul au départ) et légendes très connues manquantes.
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Andrea', 'lastname' => 'Pirlo', 'profile' => 'playmaker', 'position' => 'MDC', 'secondary' => 'MC', 'stats' => ['speed' => 54, 'stamina' => 66, 'attack' => 29, 'defense' => 20, 'shot' => 26, 'pass' => 31, 'dribble' => 24], 'note' => 'Champion du monde 2006, deux Ligues des champions avec Milan, Juventus ; régisseur lent mais d’une précision de passe et de coups francs rare.'],
        ['firstname' => 'Francesco', 'lastname' => 'Totti', 'profile' => 'second_striker', 'position' => 'AT', 'secondary' => 'MOC', 'stats' => ['speed' => 62, 'stamina' => 66, 'attack' => 36, 'shot' => 29, 'pass' => 30, 'dribble' => 28], 'note' => 'Capitaine d’un seul club, la Roma, champion du monde 2006 et Soulier d’or 2007 ; attaquant créateur à la vision exceptionnelle.'],
        ['firstname' => 'Daniele', 'lastname' => 'De Rossi', 'profile' => 'holding', 'position' => 'MC', 'secondary' => 'MDC', 'stats' => ['speed' => 60, 'stamina' => 76, 'attack' => 22, 'defense' => 29, 'shot' => 23, 'pass' => 26, 'tackle' => 27, 'intercept' => 27, 'heading' => 24], 'note' => 'Champion du monde 2006, âme de la Roma ; milieu récupérateur et relanceur, tempérament de combattant.'],
        ['firstname' => 'Gianni', 'lastname' => 'Rivera', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 66, 'attack' => 32, 'shot' => 26, 'pass' => 31, 'dribble' => 27], 'note' => 'Ballon d’Or 1969, deux Coupes d’Europe avec Milan et champion d’Europe 1968 ; le « Golden Boy » élégant, passes et vision.'],
        ['firstname' => 'Marco', 'lastname' => 'Tardelli', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 66, 'stamina' => 80, 'attack' => 26, 'defense' => 27, 'shot' => 25, 'pass' => 25, 'tackle' => 25], 'note' => 'Champion du monde 1982, auteur du but en finale et de la célébration qui l’a rendu célèbre, Juventus ; milieu d’endurance et de combat.'],
        ['firstname' => 'Giacinto', 'lastname' => 'Facchetti', 'profile' => 'wingback', 'position' => 'DG', 'secondary' => null, 'stats' => ['speed' => 72, 'stamina' => 78, 'defense' => 29, 'attack' => 24, 'pass' => 25, 'shot' => 22], 'note' => 'Capitaine de la Grande Inter, champion d’Europe 1968 ; pionnier du latéral offensif.'],
        ['firstname' => 'Gaetano', 'lastname' => 'Scirea', 'profile' => 'libero', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 74, 'defense' => 33, 'block' => 26, 'intercept' => 28, 'tackle' => 26, 'pass' => 27], 'note' => 'Champion du monde 1982 avec la Juventus, jamais expulsé en carrière ; libéro d’une élégance et d’une classe rares.'],
        ['firstname' => 'Giorgio', 'lastname' => 'Chiellini', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 60, 'stamina' => 74, 'defense' => 33, 'block' => 28, 'tackle' => 28, 'heading' => 28, 'pass' => 21], 'note' => 'Capitaine champion d’Europe 2021, neuf titres de champion avec la Juventus ; défenseur dur, agressif et intraitable de la tête.'],
        ['firstname' => 'Gigi', 'lastname' => 'Riva', 'profile' => 'striker', 'position' => 'AG', 'secondary' => 'BU', 'stats' => ['speed' => 72, 'stamina' => 70, 'attack' => 35, 'shot' => 30, 'dribble' => 24, 'heading' => 26], 'note' => 'Meilleur buteur de l’histoire de la Squadra Azzurra, champion d’Europe 1968, Cagliari ; frappe du pied gauche foudroyante.'],
    ];
}

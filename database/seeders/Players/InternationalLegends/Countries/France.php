<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/** Légendes France : profils de jeu éditables, sans statistiques de carrière réelles. */
final class France
{
    public const NATIONALITY = 'France';

    // Catalogue initial : ordre et statistiques conservés.
    public const ROSTER = [
        ['firstname' => 'Fabien', 'lastname' => 'Barthez', 'profile' => 'keeper'],
        ['firstname' => 'Hugo', 'lastname' => 'Lloris', 'profile' => 'keeper'],
        ['firstname' => 'Marcel', 'lastname' => 'Desailly', 'profile' => 'stopper'],
        ['firstname' => 'Patrick', 'lastname' => 'Vieira', 'profile' => 'box_to_box'],
        ['firstname' => 'Lilian', 'lastname' => 'Thuram', 'profile' => 'fullback'],
        ['firstname' => 'Laurent', 'lastname' => 'Blanc', 'profile' => 'libero'],
        ['firstname' => 'Bixente', 'lastname' => 'Lizarazu', 'profile' => 'wingback'],
        ['firstname' => 'Frank', 'lastname' => 'Leboeuf', 'profile' => 'stopper'],
        ['firstname' => 'Emmanuel', 'lastname' => 'Petit', 'profile' => 'holding'],
        ['firstname' => 'Claude', 'lastname' => 'Makélélé', 'profile' => 'holding'],
        ['firstname' => 'Robert', 'lastname' => 'Pirès', 'profile' => 'playmaker'],
        ['firstname' => 'Dominique', 'lastname' => 'Rocheteau', 'profile' => 'winger_forward'],
        ['firstname' => 'Sylvain', 'lastname' => 'Wiltord', 'profile' => 'striker'],
        ['firstname' => 'Youri', 'lastname' => 'Djorkaeff', 'profile' => 'second_striker'],
    ];

    // Compléments : rang du tableau conservé ; stats surcharge le profil partagé.
    public const ADDITIONS = [
        ['firstname' => 'Zinédine', 'lastname' => 'Zidane', 'profile' => 'playmaker', 'world_cup_rank' => 5, 'position' => 'MOC', 'secondary' => 'MC', 'stats' => ['attack' => 36, 'pass' => 30, 'dribble' => 29, 'shot' => 26, 'heading' => 26]],
        ['firstname' => 'Kylian', 'lastname' => 'Mbappé', 'profile' => 'winger_forward', 'world_cup_rank' => 8, 'position' => 'AG', 'secondary' => 'BU', 'stats' => ['attack' => 36, 'shot' => 29, 'dribble' => 28, 'speed' => 82]],
        ['firstname' => 'Just', 'lastname' => 'Fontaine', 'profile' => 'striker', 'world_cup_rank' => 16, 'position' => 'BU', 'secondary' => null, 'stats' => ['attack' => 34, 'shot' => 29, 'heading' => 27]],
        ['firstname' => 'Éric', 'lastname' => 'Cantona', 'profile' => 'second_striker', 'position' => 'AT', 'secondary' => 'MOC', 'stats' => ['attack' => 34, 'shot' => 27, 'pass' => 27, 'dribble' => 26, 'heading' => 27], 'note' => 'Attaquant créatif, spécialiste des remises et des combinaisons entre les lignes.'],
    ];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Thierry', 'lastname' => 'Henry', 'profile' => 'striker', 'stats' => ['attack' => 36, 'speed' => 80, 'shot' => 29, 'dribble' => 28], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'David', 'lastname' => 'Trezeguet', 'profile' => 'striker', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Didier', 'lastname' => 'Deschamps', 'profile' => 'holding', 'stats' => [], 'note' => 'Référence de la période des années 1990.'],
        ['firstname' => 'Jean-Pierre', 'lastname' => 'Papin', 'profile' => 'striker', 'stats' => ['attack' => 33, 'shot' => 28, 'heading' => 27], 'note' => 'Référence de la période des années 1990.'],
    ];
}

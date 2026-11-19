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
        ['firstname' => '', 'lastname' => 'Alisson', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 74, 'defense' => 33, 'hand_save' => 28, 'punch_save' => 28, 'pass' => 22], 'note' => 'Liverpool (Ligue des champions 2019, Premier League) et Roma ; réflexes et relance au pied.'],
        ['firstname' => 'Carlos Alberto', 'lastname' => 'Torres', 'profile' => 'fullback', 'position' => 'DD', 'secondary' => null, 'stats' => ['speed' => 70, 'stamina' => 76, 'defense' => 29, 'tackle' => 27, 'pass' => 26, 'shot' => 22], 'note' => 'Capitaine champion du monde 1970 et auteur du quatrième but de la finale ; latéral droit offensif.'],
        ['firstname' => '', 'lastname' => 'Lúcio', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 66, 'stamina' => 78, 'defense' => 32, 'block' => 27, 'tackle' => 27, 'heading' => 28], 'note' => 'Champion du monde 2002, triplé 2010 avec l’Inter, Bayern ; défenseur central puissant et conquérant.'],
        ['firstname' => 'Thiago', 'lastname' => 'Silva', 'profile' => 'libero', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 58, 'stamina' => 72, 'defense' => 32, 'intercept' => 28, 'tackle' => 27, 'pass' => 24, 'heading' => 26], 'note' => 'Ligue des champions 2021 avec Chelsea, Milan, PSG ; défenseur de lecture, placement et calme.'],
        ['firstname' => '', 'lastname' => 'Zico', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => 'AT', 'stats' => ['speed' => 66, 'stamina' => 70, 'attack' => 36, 'shot' => 28, 'pass' => 30, 'dribble' => 29], 'note' => 'Le « Pelé blanc », Libertadores 1981 avec Flamengo ; meneur et frappeur de coups francs.'],
        ['firstname' => '', 'lastname' => 'Sócrates', 'profile' => 'playmaker', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 58, 'stamina' => 66, 'attack' => 31, 'shot' => 25, 'pass' => 29, 'dribble' => 26, 'heading' => 24], 'note' => 'Capitaine de la Seleção 1982 et 1986, Corinthians ; passes du talon et vision, élégant plus que physique.'],
        ['firstname' => '', 'lastname' => 'Kaká', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => 'AG', 'stats' => ['speed' => 74, 'stamina' => 70, 'attack' => 35, 'shot' => 27, 'pass' => 29, 'dribble' => 29], 'note' => 'Ballon d’Or 2007, Milan (Ligue des champions 2007) et champion du monde 2002 ; conduites de balle et accélérations.'],
        ['firstname' => '', 'lastname' => 'Casemiro', 'profile' => 'holding', 'position' => 'MDC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 80, 'attack' => 22, 'defense' => 31, 'shot' => 22, 'block' => 25, 'intercept' => 28, 'tackle' => 28, 'heading' => 27], 'note' => 'Cinq Ligues des champions avec le Real Madrid ; récupérateur puissant, très bon de la tête.'],
        ['firstname' => '', 'lastname' => 'Bebeto', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 70, 'attack' => 33, 'shot' => 28, 'dribble' => 25], 'note' => 'Champion du monde 1994 avec Romário, Deportivo ; attaquant vif et finisseur.'],
        ['firstname' => 'Vinícius', 'lastname' => 'Júnior', 'profile' => 'winger_forward', 'position' => 'AG', 'secondary' => null, 'stats' => ['speed' => 82, 'stamina' => 74, 'attack' => 34, 'shot' => 26, 'pass' => 22, 'dribble' => 31], 'note' => 'Ligue des champions 2024 avec le Real Madrid ; ailier explosif, dribbleur de premier plan.'],
    ];
}

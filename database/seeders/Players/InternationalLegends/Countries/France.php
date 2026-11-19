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

    // Compléments de la revue France : générations 1950-1990 (Kopa, Platini, Giresse, Tigana, Bossis, Bats)
    // et générations 2010-2020 (Ribéry, Benzema, Griezmann, Pogba, Kanté, Varane).
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Michel', 'lastname' => 'Platini', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => 'AT', 'stats' => ['speed' => 62, 'stamina' => 70, 'attack' => 37, 'defense' => 20, 'shot' => 28, 'pass' => 31, 'dribble' => 28, 'heading' => 24], 'note' => 'Triple Ballon d’Or 1983-1985, meilleur buteur de l’Euro 1984 remporté avec les Bleus, Juventus ; passes décisives, coups francs et sang-froid devant le but.'],
        ['firstname' => 'Raymond', 'lastname' => 'Kopa', 'profile' => 'winger', 'position' => 'AG', 'secondary' => 'MOC', 'stats' => ['speed' => 72, 'stamina' => 72, 'attack' => 34, 'shot' => 25, 'pass' => 29, 'dribble' => 30], 'note' => 'Ballon d’Or 1958, troisième du Mondial 1958 et triple vainqueur de la Coupe d’Europe avec le Real Madrid ; dribbleur et passeur.'],
        ['firstname' => 'Alain', 'lastname' => 'Giresse', 'profile' => 'playmaker', 'position' => 'MC', 'secondary' => 'MOC', 'stats' => ['speed' => 66, 'stamina' => 74, 'attack' => 30, 'shot' => 22, 'pass' => 29, 'dribble' => 28], 'note' => 'Cœur du carré magique, champion d’Europe 1984, Bordeaux et Mondiaux 1982 et 1986 ; petit gabarit, conduite de balle et passes.'],
        ['firstname' => 'Jean', 'lastname' => 'Tigana', 'profile' => 'box_to_box', 'position' => 'MC', 'stats' => ['speed' => 68, 'stamina' => 80, 'attack' => 28, 'defense' => 24, 'shot' => 20, 'pass' => 27, 'dribble' => 28, 'tackle' => 23], 'note' => 'Poumon du carré magique, champion d’Europe 1984 et titré avec Bordeaux ; endurance, conduite de balle et récupération.'],
        ['firstname' => 'Maxime', 'lastname' => 'Bossis', 'profile' => 'wingback', 'position' => 'DG', 'secondary' => 'DC', 'stats' => ['speed' => 68, 'stamina' => 78, 'defense' => 28, 'tackle' => 26, 'shot' => 22], 'note' => 'Latéral gauche de Nantes et des Bleus champions d’Europe 1984 ; montées incessantes, frappe de loin et abattage.'],
        ['firstname' => 'Joël', 'lastname' => 'Bats', 'profile' => 'keeper', 'stats' => ['speed' => 58, 'stamina' => 70, 'defense' => 30, 'hand_save' => 27, 'punch_save' => 26], 'note' => 'Gardien champion d’Europe 1984, Auxerre puis Paris Saint-Germain ; régulier sur sa ligne.'],
        ['firstname' => 'Franck', 'lastname' => 'Ribéry', 'profile' => 'winger', 'position' => 'AG', 'secondary' => 'MG', 'stats' => ['speed' => 76, 'stamina' => 76, 'attack' => 32, 'shot' => 24, 'pass' => 27, 'dribble' => 30], 'note' => 'Bayern Munich, triplé 2013 et finaliste du Mondial 2006 ; ailier de percussion, dribbles et centres.'],
        ['firstname' => 'Karim', 'lastname' => 'Benzema', 'profile' => 'second_striker', 'position' => 'BU', 'secondary' => 'MOC', 'stats' => ['speed' => 70, 'stamina' => 74, 'attack' => 36, 'shot' => 29, 'pass' => 28, 'dribble' => 27, 'heading' => 24], 'note' => 'Ballon d’Or 2022, cinq Ligues des champions avec le Real Madrid ; avant-centre créatif, finition et jeu de liaison.'],
        ['firstname' => 'Antoine', 'lastname' => 'Griezmann', 'profile' => 'second_striker', 'position' => 'MOC', 'secondary' => 'AD', 'stats' => ['speed' => 74, 'stamina' => 80, 'attack' => 33, 'defense' => 22, 'shot' => 27, 'pass' => 28, 'dribble' => 27, 'intercept' => 22, 'tackle' => 20, 'heading' => 22], 'note' => 'Champion du monde 2018, meilleur joueur et meilleur buteur de l’Euro 2016, Atlético de Madrid ; attaquant complet, très actif dans le repli.'],
        ['firstname' => 'Paul', 'lastname' => 'Pogba', 'profile' => 'box_to_box', 'position' => 'MC', 'stats' => ['speed' => 68, 'stamina' => 76, 'attack' => 30, 'defense' => 25, 'shot' => 25, 'pass' => 28, 'dribble' => 26, 'tackle' => 24, 'heading' => 25], 'note' => 'Champion du monde 2018 et quadruple champion d’Italie avec la Juventus ; puissance, frappe de loin et passes longues.'],
        ['firstname' => 'N’Golo', 'lastname' => 'Kanté', 'profile' => 'holding', 'position' => 'MDC', 'stats' => ['speed' => 72, 'stamina' => 82, 'attack' => 20, 'defense' => 31, 'shot' => 14, 'pass' => 22, 'dribble' => 21, 'block' => 25, 'intercept' => 29, 'tackle' => 28], 'note' => 'Champion du monde 2018, titré avec Leicester et Chelsea (Ligue des champions 2021) ; récupérateur infatigable, très peu offensif.'],
        ['firstname' => 'Raphaël', 'lastname' => 'Varane', 'profile' => 'stopper', 'position' => 'DC', 'stats' => ['speed' => 70, 'stamina' => 74, 'defense' => 33, 'block' => 28, 'intercept' => 28, 'tackle' => 28, 'heading' => 28, 'pass' => 22], 'note' => 'Champion du monde 2018, quatre Ligues des champions avec le Real Madrid ; défenseur rapide, anticipation et jeu de tête.'],
    ];
}

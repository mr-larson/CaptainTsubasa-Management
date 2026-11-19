<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class Bulgaria
{
    public const NATIONALITY = 'Bulgarie';
    public const ROSTER = [];
    public const ADDITIONS = [];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Hristo', 'lastname' => 'Stoichkov', 'profile' => 'striker', 'stats' => ['attack' => 34, 'shot' => 28, 'dribble' => 27], 'note' => 'Référence de la période 1994–2002.'],
    ];

    // Onze historique, complété par Berbatov ; Stoichkov reste dans HISTORICAL_ADDITIONS.
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Borislav', 'lastname' => 'Mihaylov', 'profile' => 'keeper', 'stats' => ['speed' => 60, 'stamina' => 74, 'defense' => 31, 'hand_save' => 28, 'punch_save' => 26], 'note' => 'Gardien et capitaine de la génération bulgare demi-finaliste du Mondial 1994.'],
        ['firstname' => 'Aleksandar', 'lastname' => 'Shalamanov', 'profile' => 'fullback', 'stats' => ['speed' => 71, 'stamina' => 78, 'defense' => 29, 'tackle' => 27, 'intercept' => 27], 'note' => 'Arrière droit de la Bulgarie des années 1960, athlétique et régulier sous le maillot du Slavia Sofia.'],
        ['firstname' => 'Trifon', 'lastname' => 'Ivanov', 'profile' => 'stopper', 'stats' => ['speed' => 59, 'stamina' => 79, 'defense' => 32, 'shot' => 20, 'block' => 29, 'tackle' => 30, 'heading' => 29], 'note' => 'Le Loup bulgare, défenseur combatif de la génération 1994, puissant dans les duels et dangereux sur les frappes lointaines.'],
        ['firstname' => 'Dimitar', 'lastname' => 'Penev', 'profile' => 'libero', 'stats' => ['stamina' => 78, 'defense' => 31, 'intercept' => 29, 'pass' => 27, 'heading' => 27], 'note' => 'Défenseur organisateur du CSKA Sofia et de la Bulgarie des années 1960–1970 ; représenté ici durant sa carrière de joueur.'],
        ['firstname' => 'Boris', 'lastname' => 'Gaganelov', 'profile' => 'fullback', 'stats' => ['speed' => 64, 'stamina' => 77, 'defense' => 28, 'block' => 25, 'intercept' => 26, 'heading' => 25], 'note' => 'Défenseur du CSKA Sofia, capable de couvrir le côté gauche et l’axe ; référence de la sélection bulgare des années 1960.'],
        ['firstname' => 'Stiliyan', 'lastname' => 'Petrov', 'profile' => 'box_to_box', 'stats' => ['speed' => 68, 'stamina' => 83, 'defense' => 28, 'pass' => 28, 'intercept' => 27, 'tackle' => 27], 'note' => 'Milieu endurant du Celtic puis d’Aston Villa, capitaine bulgare et relais collectif entre récupération et relance.'],
        ['firstname' => 'Krasimir', 'lastname' => 'Balakov', 'profile' => 'playmaker', 'stats' => ['speed' => 68, 'stamina' => 76, 'attack' => 31, 'shot' => 25, 'pass' => 32, 'dribble' => 30], 'note' => 'Meneur de jeu gaucher du Sporting puis de Stuttgart, créateur majeur de la Bulgarie demi-finaliste en 1994.'],
        ['firstname' => 'Hristo', 'lastname' => 'Bonev', 'profile' => 'playmaker', 'secondary' => 'AT', 'stats' => ['speed' => 66, 'stamina' => 75, 'attack' => 32, 'shot' => 27, 'pass' => 30, 'dribble' => 28], 'note' => 'Zuma, meneur offensif et buteur emblématique du Lokomotiv Plovdiv et de la Bulgarie des années 1970.'],
        ['firstname' => 'Emil', 'lastname' => 'Kostadinov', 'profile' => 'winger_forward', 'stats' => ['speed' => 77, 'stamina' => 75, 'attack' => 32, 'shot' => 27, 'dribble' => 28], 'note' => 'Attaquant de couloir de Porto puis du Bayern, décisif lors de la qualification bulgare pour le Mondial 1994.'],
        ['firstname' => 'Georgi', 'lastname' => 'Asparuhov', 'profile' => 'striker', 'stats' => ['speed' => 70, 'stamina' => 76, 'attack' => 34, 'shot' => 29, 'pass' => 25, 'dribble' => 27, 'heading' => 29], 'note' => 'Gundi, avant-centre historique du Levski Sofia, élégant, complet et efficace dans les airs comme dans la surface.'],
        ['firstname' => 'Dimitar', 'lastname' => 'Berbatov', 'profile' => 'second_striker', 'stats' => ['speed' => 61, 'stamina' => 71, 'attack' => 33, 'shot' => 28, 'pass' => 27, 'dribble' => 29, 'heading' => 28], 'note' => 'Avant-centre technique passé par Leverkusen, Tottenham et Manchester United ; toucher de balle et remises privilégiés à la vitesse.'],
        ['firstname' => 'Yordan', 'lastname' => 'Letchkov', 'profile' => 'playmaker', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 76, 'attack' => 29, 'defense' => 22, 'shot' => 26, 'pass' => 28, 'dribble' => 25, 'heading' => 28], 'note' => 'Demi-finaliste du Mondial 1994, auteur du but de tête qui élimine l’Allemagne, Hambourg et Marseille ; milieu à la tête rasée, jeu de tête et arrivées.'],
        ['firstname' => 'Martin', 'lastname' => 'Petrov', 'profile' => 'winger', 'position' => 'AG', 'secondary' => null, 'stats' => ['speed' => 74, 'stamina' => 72, 'attack' => 29, 'shot' => 25, 'pass' => 25, 'dribble' => 26, 'cross' => 27], 'note' => 'Atlético de Madrid, Manchester City, Wolfsburg ; ailier gauche rapide, centres et frappes.'],
    ];
}

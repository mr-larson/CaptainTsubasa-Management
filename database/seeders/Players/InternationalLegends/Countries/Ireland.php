<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class Ireland
{
    public const NATIONALITY = 'Irlande';
    public const ROSTER = [];
    public const ADDITIONS = [];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Roy', 'lastname' => 'Keane', 'profile' => 'holding', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
    ];

    // Compléments de la revue Irlande : un seul joueur au départ (Roy Keane), douze noms très connus ajoutés.
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Shay', 'lastname' => 'Given', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 60, 'stamina' => 72, 'defense' => 33, 'hand_save' => 27, 'punch_save' => 28], 'note' => 'Plus de 130 sélections, Newcastle puis Manchester City ; gardien aux arrêts réflexes.'],
        ['firstname' => 'Packie', 'lastname' => 'Bonner', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 58, 'stamina' => 72, 'defense' => 32, 'hand_save' => 27, 'punch_save' => 27], 'note' => 'Héros du Mondial 1990 avec l’arrêt décisif contre la Roumanie, Celtic ; gardien sûr et courageux.'],
        ['firstname' => 'Paul', 'lastname' => 'McGrath', 'profile' => 'libero', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 64, 'stamina' => 70, 'defense' => 33, 'block' => 26, 'intercept' => 29, 'tackle' => 28, 'heading' => 27, 'pass' => 24], 'note' => 'Aston Villa et Manchester United, légende d’Irlande malgré des genoux fragiles ; libéro de lecture et d’élégance.'],
        ['firstname' => 'Denis', 'lastname' => 'Irwin', 'profile' => 'fullback', 'position' => 'DG', 'secondary' => 'DD', 'stats' => ['speed' => 66, 'stamina' => 78, 'defense' => 29, 'shot' => 24, 'pass' => 25], 'note' => 'Triplé 1999 avec Manchester United, 56 sélections ; latéral régulier, penalties et coups francs.'],
        ['firstname' => 'Steve', 'lastname' => 'Staunton', 'profile' => 'wingback', 'position' => 'DG', 'secondary' => null, 'stats' => ['speed' => 66, 'stamina' => 76, 'defense' => 27, 'shot' => 25, 'cross' => 25], 'note' => 'Liverpool et Aston Villa, plus de 100 sélections, Mondial 1990 et 1994 ; latéral gauche aux coups francs.'],
        ['firstname' => 'Seamus', 'lastname' => 'Coleman', 'profile' => 'fullback', 'position' => 'DD', 'secondary' => null, 'stats' => ['speed' => 72, 'stamina' => 80, 'defense' => 28, 'attack' => 24, 'cross' => 26], 'note' => 'Capitaine d’Everton, Euro 2016 ; latéral droit offensif et endurant.'],
        ['firstname' => 'Liam', 'lastname' => 'Brady', 'profile' => 'playmaker', 'position' => 'MC', 'secondary' => 'MOC', 'stats' => ['speed' => 62, 'stamina' => 68, 'attack' => 30, 'shot' => 25, 'pass' => 30, 'dribble' => 27], 'note' => 'Arsenal puis Juventus (deux Scudetti) ; meneur gaucher à la passe et au toucher exceptionnels.'],
        ['firstname' => 'Ray', 'lastname' => 'Houghton', 'profile' => 'winger', 'position' => 'MD', 'secondary' => null, 'stats' => ['speed' => 66, 'stamina' => 78, 'attack' => 26, 'shot' => 24, 'pass' => 25, 'dribble' => 22], 'note' => 'Auteur du but contre l’Angleterre à l’Euro 1988, Liverpool ; milieu droit courageux et régulier.'],
        ['firstname' => 'Damien', 'lastname' => 'Duff', 'profile' => 'winger', 'position' => 'MG', 'secondary' => null, 'stats' => ['speed' => 76, 'stamina' => 72, 'attack' => 29, 'shot' => 25, 'pass' => 24, 'dribble' => 28], 'note' => 'Blackburn, Chelsea (champion d’Angleterre 2005), Mondial 2002 ; ailier gauche vif, dribbleur.'],
        ['firstname' => 'Robbie', 'lastname' => 'Keane', 'profile' => 'second_striker', 'position' => 'BU', 'secondary' => 'AT', 'stats' => ['speed' => 70, 'stamina' => 74, 'attack' => 31, 'shot' => 27, 'pass' => 24, 'dribble' => 25], 'note' => 'Meilleur buteur de l’histoire du pays (68 buts), Tottenham puis LA Galaxy ; attaquant vif et opportuniste.'],
        ['firstname' => 'Niall', 'lastname' => 'Quinn', 'profile' => 'target', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 56, 'stamina' => 70, 'attack' => 29, 'shot' => 25, 'pass' => 22, 'heading' => 29], 'note' => 'Arsenal et Sunderland, Mondial 1990 et 2002 ; grand attaquant de la tête et des remises.'],
        ['firstname' => 'John', 'lastname' => 'Aldridge', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 70, 'attack' => 30, 'shot' => 27, 'pass' => 20, 'dribble' => 20, 'heading' => 22], 'note' => 'Liverpool et Real Sociedad, Euro 1988 et Mondial 1990 ; buteur de surface régulier.'],
    ];
}

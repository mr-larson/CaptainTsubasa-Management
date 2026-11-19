<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class Sweden
{
    public const NATIONALITY = 'Suède';
    public const ROSTER = [];
    public const ADDITIONS = [];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Pär', 'lastname' => 'Zetterberg', 'profile' => 'playmaker', 'stats' => ['pass' => 27, 'dribble' => 25], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Thomas', 'lastname' => 'Ravelli', 'profile' => 'keeper', 'position' => 'GB', 'stats' => ['speed' => 59, 'stamina' => 75, 'attack' => 14, 'defense' => 33, 'shot' => 11, 'pass' => 19, 'dribble' => 13, 'hand_save' => 28, 'punch_save' => 26, 'intercept' => 25]],
        ['firstname' => 'Roland', 'lastname' => 'Nilsson', 'profile' => 'fullback', 'position' => 'DD', 'stats' => ['speed' => 68, 'stamina' => 80, 'attack' => 20, 'defense' => 31, 'shot' => 14, 'pass' => 23, 'dribble' => 20, 'tackle' => 27, 'intercept' => 27, 'cross' => 24]],
        ['firstname' => 'Björn', 'lastname' => 'Nordqvist', 'profile' => 'stopper', 'position' => 'DC', 'stats' => ['speed' => 60, 'stamina' => 79, 'attack' => 18, 'defense' => 33, 'shot' => 14, 'pass' => 22, 'dribble' => 17, 'intercept' => 28, 'tackle' => 28, 'block' => 27, 'heading' => 27]],
        ['firstname' => 'Olof', 'lastname' => 'Mellberg', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => 'DD', 'stats' => ['speed' => 65, 'stamina' => 80, 'attack' => 18, 'defense' => 32, 'shot' => 14, 'pass' => 20, 'dribble' => 16, 'tackle' => 28, 'block' => 28, 'intercept' => 26, 'heading' => 28]],
        ['firstname' => 'Orvar', 'lastname' => 'Bergmark', 'profile' => 'fullback', 'position' => 'DG', 'secondary' => 'DD', 'stats' => ['speed' => 67, 'stamina' => 78, 'attack' => 20, 'defense' => 31, 'shot' => 15, 'pass' => 23, 'dribble' => 20, 'tackle' => 27, 'intercept' => 27, 'cross' => 23]],
        ['firstname' => 'Nils', 'lastname' => 'Liedholm', 'profile' => 'playmaker', 'position' => 'MC', 'secondary' => 'MOC', 'stats' => ['speed' => 62, 'stamina' => 77, 'attack' => 30, 'defense' => 24, 'shot' => 24, 'pass' => 30, 'dribble' => 27, 'intercept' => 24]],
        ['firstname' => 'Gunnar', 'lastname' => 'Gren', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => 'AT', 'stats' => ['speed' => 64, 'stamina' => 73, 'attack' => 32, 'defense' => 19, 'shot' => 26, 'pass' => 29, 'dribble' => 28, 'heading' => 22]],
        ['firstname' => 'Fredrik', 'lastname' => 'Ljungberg', 'profile' => 'winger', 'position' => 'MD', 'secondary' => 'MOC', 'stats' => ['speed' => 77, 'stamina' => 79, 'attack' => 31, 'defense' => 21, 'shot' => 25, 'pass' => 25, 'dribble' => 27, 'cross' => 24]],
        ['firstname' => 'Gunnar', 'lastname' => 'Nordahl', 'profile' => 'power_forward', 'position' => 'BU', 'stats' => ['speed' => 67, 'stamina' => 77, 'attack' => 37, 'defense' => 18, 'shot' => 30, 'pass' => 20, 'dribble' => 23, 'heading' => 30]],
        ['firstname' => 'Zlatan', 'lastname' => 'Ibrahimović', 'profile' => 'target', 'position' => 'BU', 'secondary' => 'AT', 'stats' => ['speed' => 65, 'stamina' => 75, 'attack' => 37, 'defense' => 18, 'shot' => 30, 'pass' => 27, 'dribble' => 29, 'heading' => 28]],
        ['firstname' => 'Henrik', 'lastname' => 'Larsson', 'profile' => 'striker', 'position' => 'BU', 'secondary' => 'AT', 'stats' => ['speed' => 74, 'stamina' => 80, 'attack' => 35, 'defense' => 21, 'shot' => 29, 'pass' => 25, 'dribble' => 26, 'heading' => 29]],
    ];

    // Compléments de la revue Suède : seulement douze joueurs au départ (un seul gardien).
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Tomas', 'lastname' => 'Brolin', 'profile' => 'second_striker', 'position' => 'MOC', 'secondary' => 'AG', 'stats' => ['speed' => 66, 'stamina' => 70, 'attack' => 31, 'defense' => 16, 'shot' => 25, 'pass' => 28, 'dribble' => 28], 'note' => '3e du Mondial 1994 et héros du pays, Parme ; meneur et buteur élégant, technique et vision.'],
        ['firstname' => 'Alexander', 'lastname' => 'Isak', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 78, 'stamina' => 72, 'attack' => 33, 'shot' => 27, 'pass' => 24, 'dribble' => 28, 'heading' => 22], 'note' => 'Real Sociedad puis Newcastle, plus grand talent suédois de sa génération ; avant-centre rapide et élégant, finisseur sang-froid.'],
        ['firstname' => 'Kurt', 'lastname' => 'Hamrin', 'profile' => 'winger', 'position' => 'AD', 'secondary' => null, 'stats' => ['speed' => 75, 'stamina' => 70, 'attack' => 33, 'shot' => 28, 'pass' => 24, 'dribble' => 27], 'note' => 'Finaliste du Mondial 1958, plus de 190 buts en Serie A avec Fiorentina et Milan ; ailier droit buteur.'],
        ['firstname' => 'Ronnie', 'lastname' => 'Hellström', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 58, 'stamina' => 72, 'defense' => 32, 'hand_save' => 27, 'punch_save' => 27], 'note' => 'Gardien du Mondial 1974 et 1978, Kaiserslautern ; sûr et régulier, longévité.'],
        ['firstname' => 'Stefan', 'lastname' => 'Schwarz', 'profile' => 'holding', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 76, 'attack' => 24, 'defense' => 26, 'shot' => 24, 'pass' => 27, 'tackle' => 24], 'note' => '3e du Mondial 1994, Arsenal, Valence, Fiorentina ; milieu défensif complet, bon relanceur.'],
    ];
}

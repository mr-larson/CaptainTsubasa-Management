<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class CzechRepublic
{
    public const NATIONALITY = 'République tchèque';
    public const ROSTER = [];
    public const ADDITIONS = [];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Pavel', 'lastname' => 'Nedvěd', 'profile' => 'box_to_box', 'stats' => [], 'note' => 'Référence de la période 1994–2002.'],
        ['firstname' => 'Daniel', 'lastname' => 'Zítka', 'profile' => 'keeper', 'stats' => [], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Jan', 'lastname' => 'Koller', 'profile' => 'target', 'stats' => ['heading' => 28, 'speed' => 55], 'note' => 'Référence de la période 2000–2010.'],
    ];

    // Compléments de la revue Tchéquie : trois joueurs au départ, onze noms très connus ajoutés.
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Petr', 'lastname' => 'Čech', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 60, 'stamina' => 74, 'defense' => 36, 'hand_save' => 29, 'punch_save' => 29], 'note' => 'Ligue des champions 2012 et quatre Premier League avec Chelsea, recordman de matchs sans but encaissé ; gardien de référence, casque légendaire.'],
        ['firstname' => 'Tomáš', 'lastname' => 'Ujfaluši', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => 'DD', 'stats' => ['speed' => 64, 'stamina' => 76, 'defense' => 31, 'block' => 26, 'tackle' => 27, 'heading' => 27, 'pass' => 20], 'note' => 'Atlético de Madrid, Hambourg, demi-finaliste de l’Euro 2004 ; défenseur dur et régulier.'],
        ['firstname' => 'Marek', 'lastname' => 'Jankulovski', 'profile' => 'wingback', 'position' => 'DG', 'secondary' => null, 'stats' => ['speed' => 76, 'stamina' => 80, 'defense' => 27, 'attack' => 25, 'shot' => 24, 'pass' => 24, 'cross' => 26], 'note' => 'Ligue des champions 2007 avec Milan, demi-finaliste de l’Euro 2004 ; latéral gauche rapide et infatigable.'],
        ['firstname' => 'Zdeněk', 'lastname' => 'Grygera', 'profile' => 'fullback', 'position' => 'DD', 'secondary' => null, 'stats' => ['speed' => 68, 'stamina' => 78, 'defense' => 28, 'tackle' => 26, 'cross' => 22], 'note' => 'Ajax puis Juventus, demi-finaliste de l’Euro 2004 ; latéral droit sérieux.'],
        ['firstname' => 'Tomáš', 'lastname' => 'Rosický', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => null, 'stats' => ['speed' => 70, 'stamina' => 70, 'attack' => 29, 'shot' => 26, 'pass' => 28, 'dribble' => 27], 'note' => 'Le Petit Mozart, Dortmund et Arsenal, demi-finaliste de l’Euro 2004 ; meneur technique, passes et frappes.'],
        ['firstname' => 'Karel', 'lastname' => 'Poborský', 'profile' => 'winger', 'position' => 'MD', 'secondary' => null, 'stats' => ['speed' => 74, 'stamina' => 76, 'attack' => 28, 'shot' => 24, 'pass' => 25, 'dribble' => 26], 'note' => 'Finaliste de l’Euro 1996 avec son lob contre le Portugal, Manchester United, Benfica ; ailier droit rapide et malin.'],
        ['firstname' => 'Tomáš', 'lastname' => 'Souček', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 82, 'attack' => 24, 'defense' => 26, 'shot' => 24, 'pass' => 24, 'tackle' => 25, 'heading' => 28], 'note' => 'Vainqueur de la Ligue Europa Conférence 2023 avec West Ham ; milieu à la tête et au volume de jeu.'],
        ['firstname' => 'Josef', 'lastname' => 'Masopust', 'profile' => 'playmaker', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 66, 'stamina' => 78, 'attack' => 30, 'shot' => 25, 'pass' => 29, 'dribble' => 27], 'note' => 'Ballon d’Or 1962, finaliste du Mondial 1962, Dukla Prague ; meneur complet, le meilleur joueur tchécoslovaque de l’histoire.'],
        ['firstname' => 'Antonín', 'lastname' => 'Panenka', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => null, 'stats' => ['speed' => 60, 'stamina' => 68, 'attack' => 28, 'shot' => 26, 'pass' => 28, 'dribble' => 24], 'note' => 'Champion d’Europe 1976 avec son penalty en vol, Bohemians et Rapid Vienne ; meneur technique et tireur de penalty.'],
        ['firstname' => 'Milan', 'lastname' => 'Baroš', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 78, 'stamina' => 74, 'attack' => 31, 'shot' => 27, 'pass' => 20, 'dribble' => 24], 'note' => 'Meilleur buteur de l’Euro 2004, Ligue des champions 2005 avec Liverpool ; attaquant très rapide et opportuniste.'],
        ['firstname' => 'Patrik', 'lastname' => 'Schick', 'profile' => 'target', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 70, 'stamina' => 72, 'attack' => 31, 'shot' => 28, 'pass' => 20, 'dribble' => 24, 'heading' => 26], 'note' => 'Meilleur buteur de l’Euro 2020, but du milieu de terrain contre l’Écosse, Leverkusen ; avant-centre technique et puissant.'],
    ];
}

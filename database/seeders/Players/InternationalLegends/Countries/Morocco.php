<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class Morocco
{
    public const NATIONALITY = 'Maroc';
    public const ROSTER = [];
    public const ADDITIONS = [];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'Mbark', 'lastname' => 'Boussoufa', 'profile' => 'playmaker', 'stats' => ['dribble' => 27, 'pass' => 26], 'note' => 'Référence de la période 2000–2010.'],
        ['firstname' => 'Badou', 'lastname' => 'Ezzaki', 'profile' => 'keeper', 'position' => 'GB', 'stats' => ['speed' => 58, 'stamina' => 73, 'attack' => 14, 'defense' => 32, 'shot' => 11, 'pass' => 18, 'dribble' => 13, 'hand_save' => 28, 'punch_save' => 26, 'intercept' => 24], 'note' => 'Surnom : Zaki.'],
        ['firstname' => 'Achraf', 'lastname' => 'Hakimi', 'profile' => 'wingback', 'position' => 'DD', 'secondary' => 'MD', 'stats' => ['speed' => 81, 'stamina' => 81, 'attack' => 28, 'defense' => 27, 'shot' => 23, 'pass' => 25, 'dribble' => 26, 'cross' => 28]],
        ['firstname' => 'Medhi', 'lastname' => 'Benatia', 'profile' => 'stopper', 'position' => 'DC', 'stats' => ['speed' => 62, 'stamina' => 76, 'attack' => 19, 'defense' => 33, 'shot' => 15, 'pass' => 21, 'dribble' => 17, 'block' => 28, 'tackle' => 28, 'heading' => 28, 'intercept' => 27]],
        ['firstname' => 'Noureddine', 'lastname' => 'Naybet', 'profile' => 'stopper', 'position' => 'DC', 'stats' => ['speed' => 64, 'stamina' => 77, 'attack' => 20, 'defense' => 33, 'shot' => 15, 'pass' => 24, 'dribble' => 19, 'intercept' => 28, 'tackle' => 27, 'heading' => 26]],
        ['firstname' => 'Abdelkrim', 'lastname' => 'El Hadrioui', 'profile' => 'fullback', 'position' => 'DG', 'secondary' => 'MG', 'stats' => ['speed' => 69, 'stamina' => 77, 'attack' => 22, 'defense' => 28, 'shot' => 16, 'pass' => 24, 'dribble' => 21, 'cross' => 26, 'tackle' => 25]],
        ['firstname' => 'Mustapha', 'lastname' => 'Hadji', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => 'MD', 'stats' => ['speed' => 72, 'stamina' => 73, 'attack' => 30, 'defense' => 20, 'shot' => 25, 'pass' => 26, 'dribble' => 28]],
        ['firstname' => 'Abdelmajid', 'lastname' => 'Dolmy', 'profile' => 'holding', 'position' => 'MDC', 'secondary' => 'MC', 'stats' => ['speed' => 60, 'stamina' => 80, 'attack' => 22, 'defense' => 30, 'shot' => 18, 'pass' => 27, 'dribble' => 23, 'intercept' => 28, 'tackle' => 26]],
        ['firstname' => 'Abdelaziz', 'lastname' => 'Bouderbala', 'profile' => 'winger', 'position' => 'MG', 'secondary' => 'MOC', 'stats' => ['speed' => 74, 'stamina' => 72, 'attack' => 29, 'defense' => 18, 'shot' => 23, 'pass' => 25, 'dribble' => 29, 'cross' => 26]],
        ['firstname' => 'Mohamed', 'lastname' => 'Timoumi', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => 'MC', 'stats' => ['speed' => 65, 'stamina' => 69, 'attack' => 30, 'defense' => 19, 'shot' => 24, 'pass' => 28, 'dribble' => 29]],
        ['firstname' => 'Larbi', 'lastname' => 'Benbarek', 'profile' => 'second_striker', 'position' => 'AT', 'secondary' => 'MOC', 'stats' => ['speed' => 71, 'stamina' => 73, 'attack' => 33, 'defense' => 18, 'shot' => 26, 'pass' => 28, 'dribble' => 29, 'heading' => 19]],
        ['firstname' => 'Ahmed', 'lastname' => 'Faras', 'profile' => 'striker', 'position' => 'BU', 'stats' => ['speed' => 67, 'stamina' => 74, 'attack' => 33, 'defense' => 18, 'shot' => 28, 'pass' => 22, 'dribble' => 25, 'heading' => 26]],
    ];

    // Compléments de la revue Maroc : seulement douze joueurs au départ (un seul gardien, aucun Tsubasa).
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Yassine', 'lastname' => 'Bounou', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 60, 'stamina' => 72, 'defense' => 33, 'hand_save' => 28, 'punch_save' => 29], 'note' => 'Bono, demi-finaliste du Mondial 2022 et vainqueur de la Ligue Europa avec Séville ; gardien aux arrêts décisifs, notamment aux tirs au but.'],
        ['firstname' => 'Hakim', 'lastname' => 'Ziyech', 'profile' => 'playmaker', 'position' => 'AD', 'secondary' => 'MOC', 'stats' => ['speed' => 66, 'stamina' => 66, 'attack' => 31, 'shot' => 27, 'pass' => 29, 'dribble' => 27], 'note' => 'Ajax, Chelsea (Ligue des champions 2021), demi-finaliste du Mondial 2022 ; gaucher créatif, centres et frappes enroulées.'],
        ['firstname' => 'Sofyan', 'lastname' => 'Amrabat', 'profile' => 'holding', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 66, 'stamina' => 82, 'attack' => 20, 'defense' => 29, 'shot' => 18, 'pass' => 25, 'tackle' => 28, 'intercept' => 27], 'note' => 'Demi-finaliste du Mondial 2022, Fiorentina ; milieu récupérateur infatigable, très peu offensif.'],
        ['firstname' => 'Youssef', 'lastname' => 'En-Nesyri', 'profile' => 'target', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 72, 'stamina' => 74, 'attack' => 31, 'shot' => 26, 'heading' => 30, 'dribble' => 21], 'note' => 'Vainqueur de la Ligue Europa 2020 avec Séville, buteur de tête contre le Portugal au Mondial 2022 ; avant-centre athlétique, détente exceptionnelle.'],
    ];
}

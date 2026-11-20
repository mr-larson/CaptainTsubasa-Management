<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class Paraguay
{
    public const NATIONALITY = 'Paraguay';
    public const ROSTER = [];
    public const ADDITIONS = [];


    // Compléments des quatre listes historiques ; numéros regroupés par poste, pas des notes.
    public const HISTORICAL_ADDITIONS = [
        ['firstname' => 'José Luis', 'lastname' => 'Chilavert', 'profile' => 'keeper', 'stats' => ['hand_save' => 27, 'punch_save' => 26, 'shot' => 20], 'note' => 'Référence de la période 1994–2002.'],
    ];

    // Compléments de la revue Paraguay : un seul joueur au départ (Chilavert), onze noms très connus ajoutés.
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Justo', 'lastname' => 'Villar', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 58, 'stamina' => 70, 'defense' => 31, 'hand_save' => 27, 'punch_save' => 27], 'note' => 'Plus de 100 sélections, Copa América 2011 (finaliste), gardien de la génération 2010 ; sûr sur sa ligne.'],
        ['firstname' => 'Carlos', 'lastname' => 'Gamarra', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 76, 'defense' => 33, 'block' => 28, 'tackle' => 29, 'intercept' => 28, 'heading' => 27, 'pass' => 20], 'note' => 'Capitaine du Mondial 1998 sans faute commise sur de nombreux matchs, Benfica, Corinthians, AEK ; défenseur propre et dominant.'],
        ['firstname' => 'Celso', 'lastname' => 'Ayala', 'profile' => 'libero', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 60, 'stamina' => 74, 'defense' => 31, 'intercept' => 27, 'tackle' => 26, 'pass' => 22, 'heading' => 26], 'note' => 'Capitaine des Albirrojas au Mondial 1998 et 2002, River Plate ; libéro de lecture et d’autorité.'],
        ['firstname' => 'Francisco', 'lastname' => 'Arce', 'profile' => 'wingback', 'position' => 'DD', 'secondary' => 'MD', 'stats' => ['speed' => 70, 'stamina' => 80, 'attack' => 26, 'defense' => 25, 'shot' => 24, 'pass' => 24, 'cross' => 28], 'note' => 'Palmeiras et Grêmio, coups de pied arrêtés ; latéral droit infatigable et centreur.'],
        ['firstname' => 'Gustavo', 'lastname' => 'Gómez', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 76, 'defense' => 32, 'block' => 27, 'tackle' => 27, 'heading' => 28, 'pass' => 22], 'note' => 'Deux Copa Libertadores avec Palmeiras, capitaine des Albirrojas ; défenseur central puissant de la tête.'],
        ['firstname' => 'Roberto', 'lastname' => 'Acuña', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 80, 'attack' => 24, 'defense' => 26, 'shot' => 22, 'pass' => 26, 'tackle' => 25], 'note' => 'Mondiaux 1998 et 2002, Saragosse puis Deportivo ; milieu de combat, volume et régularité.'],
        ['firstname' => 'Julio César', 'lastname' => 'Romero', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 66, 'attack' => 31, 'shot' => 25, 'pass' => 29, 'dribble' => 29], 'note' => 'Romerito, Copa América 1979, Mondial 1986, Fluminense et Barcelone ; meneur élégant, dribble et passe.'],
        ['firstname' => 'Miguel', 'lastname' => 'Almirón', 'profile' => 'winger', 'position' => 'AD', 'secondary' => 'MC', 'stats' => ['speed' => 76, 'stamina' => 82, 'attack' => 28, 'defense' => 22, 'shot' => 24, 'pass' => 24, 'dribble' => 26], 'note' => 'MLS Cup 2018 avec Atlanta United, Newcastle ; ailier au moteur de course et au pressing.'],
        ['firstname' => 'Roque', 'lastname' => 'Santa Cruz', 'profile' => 'target', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 70, 'stamina' => 72, 'attack' => 31, 'shot' => 27, 'pass' => 20, 'dribble' => 21, 'heading' => 27], 'note' => 'Bayern Munich (Bundesliga, Ligue des champions finaliste), Blackburn, Mondial 2006 ; avant-centre de gabarit, jeu de tête.'],
        ['firstname' => 'Salvador', 'lastname' => 'Cabañas', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 68, 'stamina' => 72, 'attack' => 32, 'shot' => 27, 'pass' => 22, 'dribble' => 24, 'heading' => 22], 'note' => 'Meilleur buteur de la Copa Libertadores 2008 avec América, Mondial 2010 ; finisseur technique et opportuniste.'],
        ['firstname' => 'José', 'lastname' => 'Cardozo', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 70, 'attack' => 31, 'shot' => 28, 'pass' => 20, 'dribble' => 20, 'heading' => 25], 'note' => 'Toluca, plus de 240 buts au Mexique, Mondial 2002 ; avant-centre de surface, finisseur régulier.'],
    ];
}

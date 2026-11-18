<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

final class Senegal
{
    public const NATIONALITY = 'Sénégal';
    public const ROSTER = [];
    public const ADDITIONS = [];
    public const HISTORICAL_ADDITIONS = [];

    // Onze historique : générations 1980, 2002 et champions d’Afrique des années 2020.
    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Édouard', 'lastname' => 'Mendy', 'profile' => 'keeper', 'stats' => ['speed' => 59, 'stamina' => 76, 'defense' => 31, 'hand_save' => 31, 'punch_save' => 29], 'note' => 'Gardien des Lions de la Teranga champions d’Afrique, vainqueur de la Ligue des champions avec Chelsea ; sa grande envergure protège la surface.'],
        ['firstname' => 'Habib', 'lastname' => 'Beye', 'profile' => 'fullback', 'position' => 'DD', 'secondary' => 'DC', 'stats' => ['speed' => 71, 'stamina' => 79, 'defense' => 28, 'tackle' => 27, 'cross' => 25], 'note' => 'Défenseur sénégalais de la génération 2002, passé par Strasbourg, Marseille et Newcastle ; latéral droit rigoureux, capable de jouer dans l’axe.'],
        ['firstname' => 'Kalidou', 'lastname' => 'Koulibaly', 'profile' => 'stopper', 'stats' => ['speed' => 69, 'stamina' => 82, 'defense' => 34, 'block' => 30, 'intercept' => 29, 'tackle' => 32, 'heading' => 30], 'note' => 'Capitaine des Lions champions d’Afrique et référence défensive de Naples ; puissant dans les duels, rapide pour couvrir ses partenaires.'],
        ['firstname' => 'Roger', 'lastname' => 'Mendy', 'profile' => 'libero', 'stats' => ['speed' => 64, 'stamina' => 77, 'defense' => 30, 'intercept' => 29, 'heading' => 28], 'note' => 'L’Impérial, défenseur des générations sénégalaises des années 1980 et 1990, passé par Toulon et Monaco ; organise la couverture et la relance.'],
        ['firstname' => 'Saliou', 'lastname' => 'Ciss', 'profile' => 'fullback', 'position' => 'DG', 'stats' => ['speed' => 72, 'stamina' => 78, 'defense' => 26, 'cross' => 25], 'note' => 'Latéral gauche des champions d’Afrique de la CAN 2021 ; endurant dans son couloir, disponible pour soutenir l’attaque.'],
        ['firstname' => 'Idrissa', 'lastname' => 'Gueye', 'profile' => 'holding', 'stats' => ['speed' => 68, 'stamina' => 87, 'defense' => 30, 'intercept' => 31, 'tackle' => 31, 'pass' => 25], 'note' => 'Milieu récupérateur passé par Lille, Everton et le PSG ; multiplie les courses, ferme les espaces et sécurise le milieu sénégalais.'],
        ['firstname' => 'Papa Bouba', 'lastname' => 'Diop', 'profile' => 'box_to_box', 'stats' => ['speed' => 64, 'stamina' => 82, 'defense' => 28, 'shot' => 25, 'block' => 25, 'heading' => 29], 'note' => 'Milieu puissant de la génération 2002, buteur contre la France en ouverture du Mondial ; fort dans le jeu aérien et les projections.'],
        ['firstname' => 'Khalilou', 'lastname' => 'Fadiga', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => 'MG', 'stats' => ['speed' => 67, 'stamina' => 74, 'attack' => 29, 'pass' => 30, 'dribble' => 28, 'cross' => 28], 'note' => 'Créateur gaucher de l’épopée sénégalaise de 2002, passé par Bruges et Auxerre ; sa vision du jeu et sa dernière passe alimentent les attaquants.'],
        ['firstname' => 'El-Hadji', 'lastname' => 'Diouf', 'profile' => 'winger_forward', 'position' => 'AD', 'stats' => ['speed' => 78, 'stamina' => 76, 'attack' => 32, 'shot' => 25, 'dribble' => 30, 'pass' => 25], 'note' => 'Attaquant de couloir emblématique de 2002, double Ballon d’or africain ; provocateur balle au pied, spécialiste des débordements et des décalages.'],
        ['firstname' => 'Jules', 'lastname' => 'Bocandé', 'profile' => 'power_forward', 'stats' => ['speed' => 68, 'stamina' => 77, 'attack' => 32, 'shot' => 28, 'heading' => 28], 'note' => 'Avant-centre des Lions des années 1980, meilleur buteur du championnat de France avec Metz ; puissant, généreux et redoutable dans la surface.'],
        ['firstname' => 'Sadio', 'lastname' => 'Mané', 'profile' => 'winger_forward', 'position' => 'AG', 'secondary' => 'BU', 'stats' => ['speed' => 83, 'stamina' => 83, 'attack' => 35, 'shot' => 30, 'dribble' => 31, 'pass' => 26, 'heading' => 25], 'note' => 'Figure offensive majeure des Lions de la Teranga et de Liverpool ; accélérations, appels et pressing au service de la finition et du collectif.'],
    ];
}

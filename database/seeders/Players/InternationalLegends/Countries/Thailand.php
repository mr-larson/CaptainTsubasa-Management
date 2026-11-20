<?php

namespace Database\Seeders\Players\InternationalLegends\Countries;

/**
 * Onze historique de Thaïlande : sélection volontairement faible, seuls les quatre personnages
 * de Captain Tsubasa (International/Countries/Thailand) tiennent un niveau élevé.
 */
final class Thailand
{
    public const NATIONALITY = 'Thaïlande';
    public const ROSTER = [];
    public const ADDITIONS = [];
    public const HISTORICAL_ADDITIONS = [];

    public const EXTRA_ADDITIONS = [
        ['firstname' => 'Kawin', 'lastname' => 'Thamsatchanan', 'profile' => 'keeper', 'position' => 'GB', 'secondary' => null, 'stats' => ['speed' => 52, 'stamina' => 62, 'attack' => 10, 'defense' => 24, 'shot' => 8, 'pass' => 14, 'dribble' => 10, 'block' => 16, 'intercept' => 17, 'tackle' => 13, 'heading' => 12, 'hand_save' => 24, 'punch_save' => 23], 'note' => 'Gardien de la sélection pendant douze ans (69 sélections, 2010–2022), Muangthong United ; réflexes honnêtes, mais loin des standards mondiaux.'],
        ['firstname' => 'Theerathon', 'lastname' => 'Bunmathan', 'profile' => 'wingback', 'position' => 'DG', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 66, 'attack' => 15, 'defense' => 22, 'shot' => 14, 'pass' => 19, 'dribble' => 17, 'block' => 17, 'intercept' => 19, 'tackle' => 20, 'heading' => 15, 'hand_save' => 10, 'punch_save' => 10], 'note' => 'Plus de 100 sélections (2010–2026), Buriram United et passages au Japon ; latéral gauche offensif aux coups de pied arrêtés.'],
        ['firstname' => 'Niweat', 'lastname' => 'Siriwong', 'profile' => 'stopper', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 55, 'stamina' => 64, 'attack' => 12, 'defense' => 24, 'shot' => 10, 'pass' => 15, 'dribble' => 12, 'block' => 20, 'intercept' => 20, 'tackle' => 21, 'heading' => 22, 'hand_save' => 10, 'punch_save' => 10], 'note' => '90 sélections (1997–2012), capitaine de la sélection ; défenseur central solide dans les airs.'],
        ['firstname' => 'Natee', 'lastname' => 'Thongsookkaew', 'profile' => 'libero', 'position' => 'DC', 'secondary' => null, 'stats' => ['speed' => 54, 'stamina' => 64, 'attack' => 12, 'defense' => 23, 'shot' => 10, 'pass' => 16, 'dribble' => 12, 'block' => 19, 'intercept' => 21, 'tackle' => 20, 'heading' => 19, 'hand_save' => 10, 'punch_save' => 10], 'note' => '87 sélections (1986–2000) ; défenseur central de la grande génération des années 1990.'],
        ['firstname' => 'Narubodin', 'lastname' => 'Weerawatnodom', 'profile' => 'fullback', 'position' => 'DD', 'secondary' => null, 'stats' => ['speed' => 64, 'stamina' => 68, 'attack' => 15, 'defense' => 20, 'shot' => 13, 'pass' => 17, 'dribble' => 16, 'block' => 16, 'intercept' => 18, 'tackle' => 19, 'heading' => 14, 'hand_save' => 10, 'punch_save' => 10], 'note' => 'Latéral droit de Buriram United, environ 45 sélections depuis 2013 ; endurant et vif sur son côté.'],
        ['firstname' => 'Totchtawan', 'lastname' => 'Sripan', 'profile' => 'box_to_box', 'position' => 'MC', 'secondary' => null, 'stats' => ['speed' => 60, 'stamina' => 68, 'attack' => 19, 'defense' => 17, 'shot' => 17, 'pass' => 21, 'dribble' => 19, 'block' => 14, 'intercept' => 18, 'tackle' => 17, 'heading' => 14, 'hand_save' => 10, 'punch_save' => 10], 'note' => '110 sélections (1993–2009), 19 buts ; milieu de terrain complet et buteur à ses heures.'],
        ['firstname' => 'Witthaya', 'lastname' => 'Laohakul', 'profile' => 'holding', 'position' => 'MDC', 'secondary' => null, 'stats' => ['speed' => 56, 'stamina' => 70, 'attack' => 14, 'defense' => 20, 'shot' => 13, 'pass' => 20, 'dribble' => 15, 'block' => 16, 'intercept' => 21, 'tackle' => 20, 'heading' => 15, 'hand_save' => 10, 'punch_save' => 10], 'note' => 'Une centaine de sélections (1977–1987) ; milieu récupérateur et capitaine historique.'],
        ['firstname' => 'Chanathip', 'lastname' => 'Songkrasin', 'profile' => 'playmaker', 'position' => 'MOC', 'secondary' => null, 'stats' => ['speed' => 66, 'stamina' => 66, 'attack' => 21, 'defense' => 12, 'shot' => 18, 'pass' => 23, 'dribble' => 24, 'block' => 10, 'intercept' => 14, 'tackle' => 12, 'heading' => 10, 'hand_save' => 10, 'punch_save' => 10], 'note' => '« Messi Jay », 81 sélections et 15 buts, Consadole Sapporo ; petit meneur technique, le plus doué de sa génération.'],
        ['firstname' => 'Kiatisuk', 'lastname' => 'Senamuang', 'profile' => 'second_striker', 'position' => 'AT', 'secondary' => 'BU', 'stats' => ['speed' => 62, 'stamina' => 64, 'attack' => 24, 'defense' => 11, 'shot' => 22, 'pass' => 17, 'dribble' => 20, 'block' => 9, 'intercept' => 11, 'tackle' => 10, 'heading' => 16, 'hand_save' => 10, 'punch_save' => 10], 'note' => '« Zico thaïlandais » : 131 sélections et 73 buts (1993–2007), meilleur buteur de l’histoire du pays.'],
        ['firstname' => 'Teerasil', 'lastname' => 'Dangda', 'profile' => 'striker', 'position' => 'BU', 'secondary' => null, 'stats' => ['speed' => 62, 'stamina' => 64, 'attack' => 23, 'defense' => 10, 'shot' => 21, 'pass' => 15, 'dribble' => 18, 'block' => 8, 'intercept' => 10, 'tackle' => 9, 'heading' => 18, 'hand_save' => 10, 'punch_save' => 10], 'note' => '130 sélections et 64 buts (2007–2026), Muangthong United et passages en Espagne et au Japon ; buteur opportuniste.'],
        ['firstname' => 'Piyapong', 'lastname' => 'Pue-on', 'profile' => 'striker', 'position' => 'AT', 'secondary' => 'BU', 'stats' => ['speed' => 64, 'stamina' => 64, 'attack' => 23, 'defense' => 10, 'shot' => 21, 'pass' => 15, 'dribble' => 19, 'block' => 8, 'intercept' => 10, 'tackle' => 9, 'heading' => 17, 'hand_save' => 10, 'punch_save' => 10], 'note' => '100 sélections et 70 buts (1981–1997) ; attaquant légendaire des années 1980.'],
    ];
}

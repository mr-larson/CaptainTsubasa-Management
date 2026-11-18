<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/** Personnages du World Youth ; statistiques adaptées au niveau du catalogue. */
final class SouthKorea
{
    public static function players(): array
    {
        return [
            ['Yong-un', 'Lee', 13, 'Forward', 0,
                ['speed' => 77, 'stamina' => 80, 'attack' => 36, 'defense' => 21, 'shot' => 28, 'pass' => 26, 'dribble' => 29, 'block' => 18, 'intercept' => 21, 'tackle' => 20, 'hand_save' => 10, 'punch_save' => 10, 'heading' => 25],
                'Lee Yong-un, capitaine de la Corée du Sud au World Youth. Attaquant technique et mobile, partenaire de Cha In-cheon.', []],
            ['In-cheon', 'Cha', 13, 'Forward', 0,
                ['speed' => 70, 'stamina' => 82, 'attack' => 37, 'defense' => 23, 'shot' => 29, 'pass' => 21, 'dribble' => 26, 'block' => 21, 'intercept' => 20, 'tackle' => 23, 'hand_save' => 10, 'punch_save' => 10, 'heading' => 30],
                'Cha In-cheon, attaquant puissant du World Youth coréen. Son jeu de percussion et sa frappe privilégient la force à la création.', []],
        ];
    }
}

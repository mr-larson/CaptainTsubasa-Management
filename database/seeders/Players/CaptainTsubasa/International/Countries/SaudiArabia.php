<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/** Personnages du World Youth ; statistiques adaptées au niveau du catalogue. */
final class SaudiArabia
{
    public static function players(): array
    {
        return [
            ['Mark', 'Owairan', 13, 'Defender', 0,
                ['speed' => 74, 'stamina' => 82, 'attack' => 28, 'defense' => 36, 'shot' => 23, 'pass' => 28, 'dribble' => 26, 'block' => 29, 'intercept' => 31, 'tackle' => 30, 'hand_save' => 10, 'punch_save' => 10, 'heading' => 28],
                'Mark Owairan, capitaine et libéro saoudien du World Youth. Organise la défense et coupe les trajectoires avant de relancer.', ['Midfielder']],
            ['', 'Vulcan', 13, 'Forward', 0,
                ['speed' => 56, 'stamina' => 76, 'attack' => 36, 'defense' => 28, 'shot' => 28, 'pass' => 18, 'dribble' => 18, 'block' => 27, 'intercept' => 20, 'tackle' => 24, 'hand_save' => 10, 'punch_save' => 10, 'heading' => 34],
                'Vulcan, géant saoudien du World Youth. Redoutable de la tête et dans les duels physiques ; sa mobilité et sa technique au sol restent limitées. Peut renforcer la défense.', ['Defender']],
        ];
    }
}

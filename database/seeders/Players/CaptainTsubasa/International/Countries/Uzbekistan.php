<?php

namespace Database\Seeders\Players\CaptainTsubasa\International\Countries;

/** Zangiev, confirmé sur la fiche officielle de World Fighters (Bandai Namco). */
final class Uzbekistan
{
    public static function players(): array
    {
        return [
            ['', 'Zangiev', 13, 'Forward', 0,
                ['speed' => 63, 'stamina' => 80, 'attack' => 34, 'defense' => 22,
                    'shot' => 27, 'pass' => 19, 'dribble' => 22, 'block' => 21,
                    'intercept' => 18, 'tackle' => 23, 'hand_save' => 10, 'punch_save' => 10, 'heading' => 30],
                'Attaquant et capitaine de l’Ouzbékistan Youth. Grand et puissant, Zangiev privilégie les duels physiques et les actions de percussion ; sa vitesse et sa technique restent limitées.', []],
        ];
    }
}

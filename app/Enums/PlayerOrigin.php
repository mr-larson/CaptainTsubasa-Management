<?php

namespace App\Enums;

/** Origines du catalogue, indépendantes de la nationalité d'un joueur. */
final class PlayerOrigin
{
    public const INTERNATIONAL = 'captain_tsubasa_international';
    public const LEGENDS = 'world_legends';

    public const LABELS = [
        'captain_tsubasa' => 'Captain Tsubasa',
        self::INTERNATIONAL => 'Captain Tsubasa — Internationaux',
        self::LEGENDS => 'Légendes internationales',
        'ecole_des_champions' => 'École des Champions',
        'hungry_heart' => 'Hungry Heart',
        'blue_lock' => 'Blue Lock',
        'ao_ashi' => 'Ao Ashi',
        'original' => 'Originaux',
        'amateur' => 'Amateurs',
        'fictional' => 'Fictifs',
    ];

    public static function forNationality(string $origin, ?string $nationality): string
    {
        return $origin === 'captain_tsubasa' && $nationality !== null && $nationality !== Nationality::JAPON
            ? self::INTERNATIONAL : $origin;
    }
}

<?php

namespace Database\Seeders\Players;

use Database\Seeders\Players\Clubs\Nankatsu;
use Database\Seeders\Players\Clubs\Shutetsu;
use Database\Seeders\Players\Clubs\Toho;
use Database\Seeders\Players\Clubs\Furano;
use Database\Seeders\Players\Clubs\Musashi;
use Database\Seeders\Players\Clubs\Hanawa;
use Database\Seeders\Players\Clubs\Azumaichi;
use Database\Seeders\Players\Clubs\Hirado;
use Database\Seeders\Players\Clubs\Otomo;
use Database\Seeders\Players\Clubs\Meiwa;
use Database\Seeders\Players\Clubs\Nakahara;
use Database\Seeders\Players\Clubs\Naniwa;
use Database\Seeders\Players\Clubs\Minawi;
use Database\Seeders\Players\Clubs\Shimizu;
use Database\Seeders\Players\Clubs\Shimada;
use Database\Seeders\Players\Clubs\TeamCoreSpecialMoves;

/**
 * Données joueurs — groupe : les 15 clubs de Captain Tsubasa (collège).
 *
 * Point d'entrée lu par PlayerSeeder. Les données vivent dans une classe par club
 * (database/seeders/Players/Clubs/) ; cette classe les assemble dans l'ordre exact
 * qui fixe les ids du catalogue : tous les titulaires, club après club, puis tous
 * les remplaçants, club après club. Les ids en base en dépendent : ne pas changer
 * l'ordre des listes ci-dessous.
 */
class CaptainTsubasaClubPlayers
{
    /** Clubs, dans l'ordre du championnat (même ordre que ClubRosters). */
    private const CLUBS = [
        Nankatsu::class,
        Shutetsu::class,
        Toho::class,
        Furano::class,
        Musashi::class,
        Hanawa::class,
        Azumaichi::class,
        Hirado::class,
        Otomo::class,
        Meiwa::class,
        Nakahara::class,
        Naniwa::class,
        Minawi::class,
        Shimizu::class,
        Shimada::class,
    ];

    /** Clubs ayant des coups spéciaux, dans l'ordre d'assemblage. */
    private const SPECIAL_MOVE_CLUBS = [
        Nankatsu::class,
        Shutetsu::class,
        Toho::class,
        Furano::class,
        Musashi::class,
        Hanawa::class,
        Hirado::class,
        Otomo::class,
        Azumaichi::class,
        Nakahara::class,
        Naniwa::class,
    ];

    /**
     * Tous les joueurs des clubs : titulaires de chaque club, puis remplaçants.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function players(): array
    {
        return array_merge(
            ...array_map(fn (string $club) => $club::starters(), self::CLUBS),
            ...array_map(fn (string $club) => $club::substitutes(), self::CLUBS),
        );
    }

    /**
     * Coups spéciaux de tous les joueurs de clubs, indexés par slug « prenom-nom ».
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function specialMoves(): array
    {
        return array_merge(
            ...array_map(fn (string $club) => $club::specialMoves(), self::SPECIAL_MOVE_CLUBS),
            ...[TeamCoreSpecialMoves::specialMoves()],
        );
    }
}

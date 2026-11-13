<?php

namespace App\Enums;

/** Statut d'un match : programmé, joué ou annulé. */
enum MatchStatus: string
{
    case Scheduled = 'scheduled';
    case Played    = 'played';
    case Cancelled = 'cancelled';
}

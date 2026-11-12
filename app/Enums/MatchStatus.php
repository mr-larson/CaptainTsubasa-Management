<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Scheduled = 'scheduled';
    case Played    = 'played';
    case Cancelled = 'cancelled';
}

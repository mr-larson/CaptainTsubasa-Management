<?php

namespace App\Enums;

/** Sur qui / quoi une carte bonus ou malus agit. */
enum BonusCardTarget: string
{
    case Self_    = 'self';
    case Player   = 'player';
    case Match    = 'match';
    case Finance  = 'finance';
    case Opponent = 'opponent';
    case Team     = 'team';
}

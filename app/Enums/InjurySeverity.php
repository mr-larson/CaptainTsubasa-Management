<?php

namespace App\Enums;

enum InjurySeverity: string
{
    case Light    = 'light';
    case Moderate = 'moderate';
    case Severe   = 'severe';
}

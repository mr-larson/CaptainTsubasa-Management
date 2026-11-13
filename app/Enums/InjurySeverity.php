<?php

namespace App\Enums;

/** Gravité d'une blessure : légère, modérée ou sévère. */
enum InjurySeverity: string
{
    case Light    = 'light';
    case Moderate = 'moderate';
    case Severe   = 'severe';
}

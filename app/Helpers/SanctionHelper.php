<?php

namespace App\Helpers;

class SanctionHelper 
{
    /**
     * Convertit une durée en semaines en une durée en jours.
     *
     * @param int $durationInWeeks
     * @return int
     */
    public static function convertWeeksToDays(int $durationInWeeks): int
    {
        return $durationInWeeks * 7;
    }

}

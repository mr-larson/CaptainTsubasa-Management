<?php

namespace App\Models\Traits;

trait HasSoccerStats
{
    /**
     * Les classes qui utilisent ce trait doivent implémenter
     * cette méthode pour retourner la valeur brute d’une stat.
     */
    abstract protected function getBaseStat(string $key): int;

    // --- Offensif ---

    public function shotStat(): int
    {
        return $this->getBaseStat('shot');
    }

    public function passStat(): int
    {
        return $this->getBaseStat('pass');
    }

    public function dribbleStat(): int
    {
        return $this->getBaseStat('dribble');
    }

    // --- Défensif ---

    public function blockStat(): int
    {
        return $this->getBaseStat('block');
    }

    public function interceptionStat(): int
    {
        return $this->getBaseStat('intercept');
    }

    public function tackleStat(): int
    {
        return $this->getBaseStat('tackle');
    }

    // --- Spé offensif / défensif ---

    public function offensiveSpecialStat(): int
    {
        return $this->getBaseStat('attack');
    }

    public function defensiveSpecialStat(): int
    {
        return $this->getBaseStat('defense');
    }

    // --- Gardien ---

    public function gkCatchStat(): int
    {
        return $this->getBaseStat('hand_save');
    }

    public function gkPunchStat(): int
    {
        return $this->getBaseStat('punch_save');
    }
}

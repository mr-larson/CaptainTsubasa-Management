<?php

namespace App\Models\Traits;

/**
 * Accesseurs nommés des stats d'un joueur (tir, passe, dribble, blocage…) pour le
 * moteur de match. Chaque classe qui l'utilise dit où lire la valeur brute (getBaseStat).
 */
trait HasSoccerStats
{
    /**
     * Les classes qui utilisent ce trait doivent implémenter
     * cette méthode pour retourner la valeur brute d’une stat.
     */
    abstract protected function getBaseStat(string $key): int;

    // --- Offensif ---

    /** Stat de tir. */
    public function shotStat(): int
    {
        return $this->getBaseStat('shot');
    }

    /** Stat de passe. */
    public function passStat(): int
    {
        return $this->getBaseStat('pass');
    }

    /** Stat de dribble. */
    public function dribbleStat(): int
    {
        return $this->getBaseStat('dribble');
    }

    // --- Défensif ---

    /** Stat de blocage. */
    public function blockStat(): int
    {
        return $this->getBaseStat('block');
    }

    /** Stat d'interception. */
    public function interceptionStat(): int
    {
        return $this->getBaseStat('intercept');
    }

    /** Stat de tacle. */
    public function tackleStat(): int
    {
        return $this->getBaseStat('tackle');
    }

    /** Stat de jeu de tête. */
    public function headingStat(): int
    {
        return $this->getBaseStat('heading');
    }

    // --- Spé offensif / défensif ---

    /** Stat servant aux coups spéciaux offensifs (l'attaque). */
    public function offensiveSpecialStat(): int
    {
        return $this->getBaseStat('attack');
    }

    /** Stat servant aux coups spéciaux défensifs (la défense). */
    public function defensiveSpecialStat(): int
    {
        return $this->getBaseStat('defense');
    }

    // --- Gardien ---

    /** Stat d'arrêt à la main du gardien. */
    public function gkCatchStat(): int
    {
        return $this->getBaseStat('hand_save');
    }

    /** Stat de dégagement du poing du gardien. */
    public function gkPunchStat(): int
    {
        return $this->getBaseStat('punch_save');
    }
}

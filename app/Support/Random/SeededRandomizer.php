<?php

namespace App\Support\Random;

use App\Contracts\Randomizer;

/**
 * Générateur déterministe (xorshift32) : une même graine donne toujours la
 * même suite de tirages, sans toucher à l'état global de mt_rand().
 * Destiné aux tests et au rejeu ; la qualité statistique suffit pour un jeu.
 */
class SeededRandomizer implements Randomizer
{
    private int $state;

    /** Initialise le générateur avec une graine (une graine nulle est remplacée par une constante). */
    public function __construct(int $seed)
    {
        $this->state = ($seed & 0xFFFFFFFF) ?: 0x9E3779B9; // xorshift ne supporte pas l'état 0
    }

    /** Entier pseudo-aléatoire entre $min et $max inclus, déterministe pour la graine. */
    public function int(int $min, int $max): int
    {
        return $min + $this->next() % ($max - $min + 1);
    }

    /** Élément choisi dans la liste de façon déterministe. */
    public function pick(array $items): mixed
    {
        $items = array_values($items);

        return $items[$this->int(0, count($items) - 1)];
    }

    /** Liste mélangée de façon déterministe (Fisher-Yates), indexée de 0 à n-1. */
    public function shuffle(array $items): array
    {
        $items = array_values($items);
        for ($i = count($items) - 1; $i > 0; $i--) {
            $j = $this->int(0, $i);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        return $items;
    }

    /** Avance l'état xorshift32 et renvoie le nouvel entier 32 bits. */
    private function next(): int
    {
        $x = $this->state;
        $x ^= ($x << 13) & 0xFFFFFFFF;
        $x ^= $x >> 17;
        $x ^= ($x << 5) & 0xFFFFFFFF;

        return $this->state = $x;
    }
}

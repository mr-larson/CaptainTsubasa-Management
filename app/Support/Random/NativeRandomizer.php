<?php

namespace App\Support\Random;

use App\Contracts\Randomizer;

/** Aléatoire cryptographique du moteur PHP : comportement de production. */
class NativeRandomizer implements Randomizer
{
    /** Entier aléatoire entre $min et $max inclus. */
    public function int(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    /** Élément choisi au hasard dans la liste. */
    public function pick(array $items): mixed
    {
        $items = array_values($items);

        return $items[random_int(0, count($items) - 1)];
    }

    /** Liste mélangée (algorithme de Fisher-Yates), indexée de 0 à n-1. */
    public function shuffle(array $items): array
    {
        $items = array_values($items);
        for ($i = count($items) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        return $items;
    }
}

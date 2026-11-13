<?php

namespace App\Contracts;

/**
 * Source d'aléatoire injectable. En production : NativeRandomizer
 * (random_int). En test ou pour rejouer un match : SeededRandomizer, qui
 * redonne la même suite de tirages pour une même graine.
 */
interface Randomizer
{
    /** Entier uniforme dans [$min, $max] inclus. */
    public function int(int $min, int $max): int;

    /**
     * Une valeur au hasard parmi $items (clés ignorées).
     *
     * @template T
     * @param  array<array-key, T>  $items  non vide
     * @return T
     */
    public function pick(array $items): mixed;

    /**
     * Copie mélangée de $items (clés perdues).
     *
     * @template T
     * @param  array<array-key, T>  $items
     * @return list<T>
     */
    public function shuffle(array $items): array;
}

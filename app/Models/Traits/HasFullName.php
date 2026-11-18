<?php

namespace App\Models\Traits;

/**
 * Expose un accessor `full_name` pour tout modèle disposant
 * de colonnes `firstname` et `lastname`.
 */
trait HasFullName
{
    /** Les noms d'usage uniques (Pelé, Didi…) n'ont pas de prénom distinct. */
    public function setFirstnameAttribute(?string $value): void
    {
        $this->attributes['firstname'] = $value ?? '';
    }

    /** Nom complet « prénom nom ». */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->firstname} {$this->lastname}");
    }
}

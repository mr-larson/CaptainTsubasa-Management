<?php

namespace App\Models\Traits;

/**
 * Expose un accessor `full_name` pour tout modèle disposant
 * de colonnes `firstname` et `lastname`.
 */
trait HasFullName
{
    public function getFullNameAttribute(): string
    {
        return trim("{$this->firstname} {$this->lastname}");
    }
}

<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * Expose un accessor `photo_url` résolvant `photo_path` depuis
 * le disque public (`/storage/...`).
 */
trait HasPhotoUrl
{
    /** URL publique de la photo, ou null si le joueur n'en a pas. */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }
}

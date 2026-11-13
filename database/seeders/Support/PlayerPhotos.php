<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Photo d'un joueur de référence.
 * Source de vérité unique : public/storage/players/ (versionné dans le repo — le
 * FS de prod est éphémère). Les .webp y sont générés par `images:publish`
 * depuis public/images, nommés firstname-lastname.webp. Le seeder ne copie rien :
 * il vérifie l'existence du fichier (webp, png/jpg en repli).
 */
final class PlayerPhotos
{
    private const DIR = 'players';

    /** Chemin relatif au disque public (ex. players/taro-misaki.webp), ou null si absent. */
    public static function pathFor(string $slug): ?string
    {
        $disk = Storage::disk('public');

        foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
            $candidate = self::DIR . '/' . $slug . '.' . $ext;
            if ($disk->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

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
    public static function pathFor(string $slug, ?string $origin = null): ?string
    {
        $disk = Storage::disk('public');

        // Les remplacements datés gardent un nom distinct pour préserver les
        // anciens fichiers/crédits et éviter de resservir une photo en cache.
        static $illustrations;
        $illustrations ??= json_decode(file_get_contents(resource_path('data/player-illustration-credits.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($illustrations as $image) {
            if ($origin !== null && isset($image['origin']) && $image['origin'] !== $origin) continue;
            if (($image['preferred_for_catalog'] ?? false)
                && ($image['player_slug'] ?? $image['slug']) === $slug
                && $disk->exists($image['photo_path'])) {
                return $image['photo_path'];
            }
        }

        // Un homonyme réel ne doit pas récupérer le portrait d'un manga (ex. Pepe).
        $legendPaths = null;
        if ($origin === 'world_legends') {
            static $photoCredits;
            $photoCredits ??= json_decode(file_get_contents(resource_path('data/player-photo-credits.json')), true, 512, JSON_THROW_ON_ERROR);
            $legendPaths = array_column(array_filter(array_merge($photoCredits, $illustrations),
                fn ($image) => ($image['player_slug'] ?? $image['slug']) === $slug), 'photo_path');
        }

        foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
            $candidate = self::DIR . '/' . $slug . '.' . $ext;
            if ($legendPaths !== null && !in_array($candidate, $legendPaths, true)) continue;
            if ($disk->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

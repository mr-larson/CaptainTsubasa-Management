<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishImagesCommand extends Command
{
    protected $signature = 'images:publish
                            {--dry-run : Liste les fichiers à générer sans rien écrire}
                            {--force : Régénère toutes les images, même à jour}';

    protected $description = 'Génère les images servies (public/storage, WebP redimensionné) depuis le dossier de dépôt (public/images) — à lancer puis committer avant tout déploiement';

    /**
     * Workflow images (le FS de prod est éphémère, tout doit être versionné) :
     *   1. Déposer les fichiers dans public/images/{players,teams}/slug.png ;
     *   2. `php artisan images:publish` génère public/storage/{players,teams}/slug.webp
     *      (redimensionné à MAX_SIZE px, compressé) — seule arborescence servie
     *      via /storage et lue par les seeders. Les anciennes variantes
     *      png/jpg du même slug y sont supprimées ;
     *   3. committer les deux dossiers, pousser, puis re-seeder si des
     *      photo_path doivent apparaître (PlayerSeeder / TeamSeeder).
     * Objectif : limiter la bande passante sortante en prod (les originaux
     * font souvent 1–2 Mo pour un affichage en vignette).
     * NB : storage/app/public est un vestige d'avant la refonte de juin 2026,
     * il ne doit servir de source à rien.
     */
    private const DIRS = ['players', 'teams'];

    private const SOURCE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];

    // Les photos s'ouvrent en plein écran (PlayerPhoto) : 640 px reste net.
    private const MAX_SIZE = 640;

    private const QUALITY = 80;

    /*
     * Variante « mode Pixel » des photos joueurs (préférence d'affichage) :
     * public/storage/players/pixel/slug.png, petite image à palette réduite,
     * agrandie côté navigateur sans lissage (image-rendering: pixelated).
     */
    private const PIXEL_SIZE   = 64;
    private const PIXEL_COLORS = 24;

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('GD avec le support WebP est requis.');

            return self::FAILURE;
        }

        $dry       = (bool) $this->option('dry-run');
        $force     = (bool) $this->option('force');
        $generated = 0;

        foreach (self::DIRS as $dir) {
            $src = public_path("images/{$dir}");
            $dst = public_path("storage/{$dir}");

            if (! File::isDirectory($src)) {
                continue;
            }
            File::ensureDirectoryExists($dst);

            foreach (File::files($src) as $file) {
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, self::SOURCE_EXTENSIONS, true)) {
                    continue;
                }

                $slug   = $file->getFilenameWithoutExtension();
                $target = "{$dst}/{$slug}.webp";
                $stale  = array_filter(
                    array_map(fn ($e) => "{$dst}/{$slug}.{$e}", ['png', 'jpg', 'jpeg']),
                    'is_file'
                );

                $upToDate = is_file($target) && filemtime($target) >= $file->getMTime();
                if ($upToDate && ! $force && $stale === []) {
                    continue;
                }

                $this->line(($dry ? '[dry-run] ' : '').(is_file($target) ? 'maj    ' : 'nouveau').'  '.$dir.'/'.$slug.'.webp');
                if (! $dry) {
                    if (! $upToDate || $force) {
                        $this->convert($file->getPathname(), $target);
                    }
                    array_map('unlink', $stale);
                }
                $generated++;
            }
        }

        $generated += $this->publishPixelVariants($dry, $force);

        if ($generated === 0) {
            $this->info('Rien à générer : public/storage est à jour.');
        } elseif ($dry) {
            $this->info("{$generated} image(s) à générer — relance sans --dry-run pour écrire.");
        } else {
            $this->info("{$generated} image(s) générée(s) — committe public/storage puis push pour mettre la prod à jour.");
        }

        return self::SUCCESS;
    }

    /**
     * Génère players/pixel/slug.png pour chaque photo joueur publiée (manquante
     * ou plus ancienne que sa source WebP).
     */
    private function publishPixelVariants(bool $dry, bool $force): int
    {
        $src = public_path('storage/players');
        $dst = public_path('storage/players/pixel');
        if (! File::isDirectory($src)) {
            return 0;
        }
        File::ensureDirectoryExists($dst);

        $count = 0;
        foreach (File::files($src) as $file) {
            if (strtolower($file->getExtension()) !== 'webp') {
                continue;
            }
            $slug   = $file->getFilenameWithoutExtension();
            $target = "{$dst}/{$slug}.png";
            if (! $force && is_file($target) && filemtime($target) >= $file->getMTime()) {
                continue;
            }

            $this->line(($dry ? '[dry-run] ' : '').(is_file($target) ? 'maj    ' : 'nouveau').'  players/pixel/'.$slug.'.png');
            if (! $dry) {
                $this->pixelate($file->getPathname(), $target);
            }
            $count++;
        }

        return $count;
    }

    /**
     * Réduit l'image à PIXEL_SIZE px (côté le plus long), binarise la
     * transparence puis limite la palette : rendu « sprite » une fois agrandi.
     */
    private function pixelate(string $source, string $target): void
    {
        $image = imagecreatefromstring(file_get_contents($source));
        if ($image === false) {
            throw new \RuntimeException("Image illisible : {$source}");
        }

        $width  = imagesx($image);
        $height = imagesy($image);
        $ratio  = self::PIXEL_SIZE / max($width, $height);
        $w      = max(1, (int) round($width * $ratio));
        $h      = max(1, (int) round($height * $ratio));

        $small = imagecreatetruecolor($w, $h);
        imagealphablending($small, false);
        imagesavealpha($small, true);
        imagefill($small, 0, 0, imagecolorallocatealpha($small, 0, 0, 0, 127));
        imagecopyresampled($small, $image, 0, 0, 0, 0, $w, $h, $width, $height);
        imagedestroy($image);

        // Transparence tout-ou-rien : pas de franges semi-transparentes.
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c     = imagecolorat($small, $x, $y);
                $alpha = ($c >> 24) & 0x7F;
                $color = $alpha > 63
                    ? imagecolorallocatealpha($small, 0, 0, 0, 127)
                    : imagecolorallocatealpha($small, ($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF, 0);
                imagesetpixel($small, $x, $y, $color);
            }
        }

        imagetruecolortopalette($small, false, self::PIXEL_COLORS);
        imagesavealpha($small, true);
        imagepng($small, $target, 9);
        imagedestroy($small);
    }

    /**
     * Redimensionne (sans agrandir) et encode en WebP, en préservant la
     * transparence des logos.
     */
    private function convert(string $source, string $target): void
    {
        $image = imagecreatefromstring(file_get_contents($source));
        if ($image === false) {
            throw new \RuntimeException("Image illisible : {$source}");
        }

        $width  = imagesx($image);
        $height = imagesy($image);
        $ratio  = min(1, self::MAX_SIZE / max($width, $height));

        if ($ratio < 1) {
            $newWidth  = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);
            $resized   = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        } else {
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        imagewebp($image, $target, self::QUALITY);
        imagedestroy($image);
    }
}

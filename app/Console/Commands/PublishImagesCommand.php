<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishImagesCommand extends Command
{
    protected $signature = 'images:publish
                            {--dry-run : Liste les fichiers à copier sans rien écrire}';

    protected $description = 'Synchronise le dossier de dépôt des images (public/images) vers les copies servies et seedées (public/storage) — à lancer puis committer avant tout déploiement';

    /**
     * Workflow images (le FS de prod est éphémère, tout doit être versionné) :
     *   1. Déposer les fichiers dans public/images/{players,teams}/slug.png ;
     *   2. `php artisan images:publish` copie vers public/storage/{players,teams}
     *      (seule arborescence servie via /storage et lue par les seeders) ;
     *   3. committer les deux dossiers, pousser, puis re-seeder si des
     *      photo_path doivent apparaître (PlayerSeeder / TeamSeeder).
     * NB : storage/app/public est un vestige d'avant la refonte de juin 2026,
     * il ne doit servir de source à rien.
     */
    private const DIRS = ['players', 'teams'];

    public function handle(): int
    {
        $dry    = (bool) $this->option('dry-run');
        $copied = 0;

        foreach (self::DIRS as $dir) {
            $src = public_path("images/{$dir}");
            $dst = public_path("storage/{$dir}");

            if (! File::isDirectory($src)) {
                continue;
            }
            File::ensureDirectoryExists($dst);

            foreach (File::files($src) as $file) {
                $target = $dst.DIRECTORY_SEPARATOR.$file->getFilename();

                if (is_file($target) && md5_file($file->getPathname()) === md5_file($target)) {
                    continue;
                }

                $this->line(($dry ? '[dry-run] ' : '').(is_file($target) ? 'maj    ' : 'nouveau').'  '.$dir.'/'.$file->getFilename());
                if (! $dry) {
                    File::copy($file->getPathname(), $target);
                }
                $copied++;
            }
        }

        if ($copied === 0) {
            $this->info('Rien à synchroniser : public/storage est à jour.');
        } elseif ($dry) {
            $this->info("{$copied} image(s) à synchroniser — relance sans --dry-run pour copier.");
        } else {
            $this->info("{$copied} image(s) synchronisée(s) — committe public/storage puis push pour mettre la prod à jour.");
        }

        return self::SUCCESS;
    }
}

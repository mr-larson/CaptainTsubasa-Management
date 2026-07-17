<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishImagesCommand extends Command
{
    protected $signature = 'images:publish
                            {--dry-run : Liste les fichiers à copier sans rien écrire}';

    protected $description = 'Synchronise les images uploadées (storage/app/public) vers les copies statiques versionnées (public/storage) à committer avant tout déploiement';

    /**
     * Le FS de Laravel Cloud est éphémère : la prod ne sert que ce qui est
     * commité dans public/storage. Les uploads de l\'éditeur de données
     * atterrissent dans storage/app/public et doivent être recopiés ici
     * puis commités, sinon la prod sert des versions obsolètes.
     */
    private const DIRS = ['players', 'teams'];

    public function handle(): int
    {
        $dry    = (bool) $this->option('dry-run');
        $copied = 0;

        foreach (self::DIRS as $dir) {
            $src = storage_path("app/public/{$dir}");
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

<?php

namespace App\Console\Commands;

use App\Models\Nation;
use Illuminate\Console\Command;

/**
 * Relie chaque nation à son drapeau image : cherche public/storage/nations/{code}.{svg,webp,png}
 * (code ISO en minuscules) et renseigne nations.flag_path. Sans effet pour les nations sans fichier.
 */
class LinkNationFlags extends Command
{
    protected $signature = 'nations:link-flags';

    protected $description = 'Relie les nations à leurs drapeaux image de public/storage/nations';

    public function handle(): int
    {
        $linked = Nation::linkFlagsFromDisk();
        $this->info("{$linked} nation(s) reliée(s) à un drapeau image sur ".Nation::count().'.');

        return self::SUCCESS;
    }
}

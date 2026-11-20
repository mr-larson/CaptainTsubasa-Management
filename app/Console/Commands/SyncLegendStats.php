<?php

namespace App\Console\Commands;

use App\Models\Player;
use Database\Seeders\Players\CaptainTsubasa\Clubs\ClubStatFiles;
use Database\Seeders\Players\CaptainTsubasa\International\InternationalStatFiles;
use Database\Seeders\Players\EcoleDesChampions\EcoleStatFiles;
use Database\Seeders\Players\InternationalLegends\Support\LegendStatFiles;
use Illuminate\Console\Command;

/**
 * Copie en base les stats des fichiers par nation (Countries/stats/*.json, légendes réelles) et par club
 * (Clubs/stats/*.json, Captain Tsubasa). Idempotent : relançable à volonté, sans migration. Les fichiers font foi : une
 * retouche faite à la main en base sur ces stats est écrasée. Les parties déjà créées gardent leurs copies.
 */
class SyncLegendStats extends Command
{
    protected $signature = 'players:sync-legend-stats {--dry-run : Affiche les changements sans écrire}';

    protected $description = 'Applique en base les stats des fichiers par nation (légendes) et par club (Captain Tsubasa)';

    public function handle(): int
    {
        // Quatre sources : fichiers par pays (légendes), par club, par nation d'internationaux (Captain Tsubasa) et de L'École des Champions.
        $sources = [
            'world_legends' => LegendStatFiles::overrides(),
            'captain_tsubasa' => ClubStatFiles::overrides(),
            'captain_tsubasa_international' => InternationalStatFiles::overrides(),
            'ecole_des_champions' => EcoleStatFiles::overrides(),
        ];

        $changed = 0;
        $missing = [];
        foreach ($sources as $origin => $overrides) {
            foreach ($overrides as $name => $override) {
                $player = Player::where('origin', $origin)
                    ->whereRaw("TRIM(CONCAT(firstname, ' ', lastname)) = ?", [$name])->first();
                if (! $player) {
                    $missing[] = $name;
                    continue;
                }

                $after = array_replace($player->stats, $override);
                if ($after == $player->stats) {
                    continue;
                }
                $changed++;
                $this->line("{$name} : ".implode(', ', array_keys(array_diff_assoc($override, $player->stats))));
                if (! $this->option('dry-run')) {
                    $player->stats = $after;
                    $player->heading = $after['heading'];
                    $player->save();
                }
            }
        }

        $this->info(($this->option('dry-run') ? 'Essai à blanc' : 'Mis à jour')." : {$changed} joueur(s).");
        if ($missing) {
            $this->warn('Introuvables en base : '.implode(', ', $missing));
        }

        return self::SUCCESS;
    }
}

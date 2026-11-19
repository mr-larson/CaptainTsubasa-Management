<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Individualise les stats des légendes réelles qui partageaient un profil type : rang de
 * stature et points forts (voir LegendTuning). L'instantané est figé dans
 * `database/data/legend-stats-tuning-2026-11-19.json`. Un joueur n'est modifié que si ses
 * stats actuelles valent encore celles d'avant (`before`) : les retouches manuelles sont conservées.
 * Les copies déjà présentes dans les parties ne changent pas.
 */
return new class extends Migration
{
    /** Applique les nouvelles stats aux légendes non retouchées. */
    public function up(): void
    {
        $snapshot = json_decode(file_get_contents(database_path('data/legend-stats-tuning-2026-11-19.json')), true, 512, JSON_THROW_ON_ERROR);

        foreach ($snapshot as $entry) {
            $this->replaceStats($entry, 'before', 'after');
        }
    }

    /** Restaure les stats d'avant, pour les joueurs encore à la valeur ajustée. */
    public function down(): void
    {
        $snapshot = json_decode(file_get_contents(database_path('data/legend-stats-tuning-2026-11-19.json')), true, 512, JSON_THROW_ON_ERROR);

        foreach ($snapshot as $entry) {
            $this->replaceStats($entry, 'after', 'before');
        }
    }

    /** Remplace les stats `$from` par `$to` si le joueur n'a pas été retouché depuis. */
    private function replaceStats(array $entry, string $from, string $to): void
    {
        $row = DB::table('players')->where('origin', 'world_legends')->whereNull('deleted_at')
            ->where('firstname', $entry['firstname'])->where('lastname', $entry['lastname'])->first();
        if (! $row) {
            return;
        }

        $current = json_decode((string) $row->stats, true) ?: [];
        foreach ($entry[$from] as $key => $value) {
            if (($current[$key] ?? null) !== $value) {
                return;
            }
        }

        DB::table('players')->where('id', $row->id)->update(['stats' => json_encode(array_merge($current, $entry[$to]))]);
    }
};

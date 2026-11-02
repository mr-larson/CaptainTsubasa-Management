<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Purge des clés de game_saves.state qui grossissaient sans limite (jusqu'à
 * ~60 % du JSON) alors qu'il est décodé à chaque requête et envoyé au front :
 *
 * - player_actions : tableau brut envoyé par le client à chaque match, jamais
 *   relu côté serveur (les stats viennent de game_matches.match_stats) ;
 * - ai_training_history / season_progression : on ne garde que la saison en
 *   cours, comme le code le fait désormais à chaque écriture.
 *
 * Irréversible : les données purgées ne sont plus utilisées.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('game_saves')->whereNotNull('state')->orderBy('id')->chunkById(50, function ($saves) {
            foreach ($saves as $save) {
                $state = json_decode($save->state, true);
                if (! is_array($state)) {
                    continue;
                }

                $season  = (int) ($save->season ?? 1);
                $changed = false;

                if (array_key_exists('player_actions', $state)) {
                    unset($state['player_actions']);
                    $changed = true;
                }

                if (isset($state['ai_training_history']) && is_array($state['ai_training_history'])) {
                    $kept = array_values(array_filter(
                        $state['ai_training_history'],
                        fn ($e) => (int) ($e['season'] ?? 0) === $season
                    ));
                    if (count($kept) !== count($state['ai_training_history'])) {
                        $state['ai_training_history'] = $kept;
                        $changed = true;
                    }
                }

                if (isset($state['season_progression']) && is_array($state['season_progression'])
                    && array_keys($state['season_progression']) !== [(string) $season]
                    && array_keys($state['season_progression']) !== [$season]) {
                    $state['season_progression'] = [$season => $state['season_progression'][$season] ?? []];
                    $changed = true;
                }

                if ($changed) {
                    DB::table('game_saves')->where('id', $save->id)->update(['state' => json_encode($state)]);
                }
            }
        });
    }

    public function down(): void
    {
        // Irréversible (données purgées non utilisées).
    }
};

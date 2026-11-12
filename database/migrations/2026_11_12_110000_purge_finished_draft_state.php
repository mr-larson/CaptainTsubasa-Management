<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * state.draft (ordre + picks, ~44 Ko) n'a plus d'utilité une fois la draft
 * terminée : on le retire des parties qui ne sont plus en phase de draft.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('game_saves')
            ->whereNotIn('phase', ['draft', 'intersaison_draft'])
            ->whereNotNull('state')
            ->select(['id', 'state'])
            ->chunkById(50, function ($saves) {
                foreach ($saves as $save) {
                    $state = json_decode($save->state, true);
                    if (! is_array($state) || ! array_key_exists('draft', $state)) {
                        continue;
                    }
                    unset($state['draft']);
                    DB::table('game_saves')->where('id', $save->id)->update(['state' => json_encode($state)]);
                }
            });
    }

    public function down(): void
    {
        // Données purgées : rien à restaurer.
    }
};

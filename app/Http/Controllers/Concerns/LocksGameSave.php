<?php

namespace App\Http\Controllers\Concerns;

use App\Models\GameSaves\GameSave;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Sérialise les actions de « tour » sur une sauvegarde (fin de match, semaine,
 * saison, draft, achats…) : une seule requête à la fois par save.
 *
 * - Transaction : une exception au milieu du pipeline annule tout, la save
 *   n'est jamais laissée à moitié avancée.
 * - SELECT … FOR UPDATE sur la ligne game_saves : un double clic ou un retry
 *   attend la fin de la première requête au lieu de rejouer l'action.
 * - refresh() sous verrou : les gardes (phase, semaine, statut…) doivent être
 *   évaluées DANS la closure, sur l'état à jour, jamais avant l'appel.
 */
trait LocksGameSave
{
    /**
     * @template T
     * @param  Closure(GameSave): T  $callback
     * @return T
     */
    protected function withSaveLock(GameSave $gameSave, Closure $callback): mixed
    {
        return DB::transaction(function () use ($gameSave, $callback) {
            GameSave::whereKey($gameSave->getKey())->lockForUpdate()->first();
            $gameSave->refresh();

            return $callback($gameSave);
        });
    }
}

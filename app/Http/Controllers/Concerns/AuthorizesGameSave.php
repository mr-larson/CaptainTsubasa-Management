<?php

namespace App\Http\Controllers\Concerns;

use App\Models\GameSaves\GameSave;
use Illuminate\Database\Eloquent\Model;

/**
 * Centralise l'autorisation des sauvegardes de partie.
 *
 * - L'ownership est délégué à GameSavePolicy (via $this->authorize()),
 *   ce qui fait bénéficier ces contrôleurs du bypass admin de Gate::before.
 * - Les ressources enfants fournies sont validées comme appartenant bien
 *   à la sauvegarde (protection contre les IDOR sur les ressources imbriquées).
 */
trait AuthorizesGameSave
{
    /**
     * @param  string  $ability      Capacité de GameSavePolicy (view, update, delete…)
     * @param  GameSave  $gameSave
     * @param  Model  ...$children    Ressources enfants exposant un game_save_id
     */
    protected function authorizeGameSave(string $ability, GameSave $gameSave, Model ...$children): void
    {
        $this->authorize($ability, $gameSave);

        foreach ($children as $child) {
            abort_unless(
                (int) $child->getAttribute('game_save_id') === (int) $gameSave->id,
                403,
                'Cette ressource n\'appartient pas à la sauvegarde.'
            );
        }
    }
}

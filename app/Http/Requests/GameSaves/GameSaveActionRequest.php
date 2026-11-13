<?php

namespace App\Http\Requests\GameSaves;

use App\Models\GameSaves\GameSave;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base des requêtes d'écriture sous /game-saves/{gameSave} : l'utilisateur doit
 * pouvoir modifier la sauvegarde (GameSavePolicy, bypass admin inclus). Les
 * ressources enfants sont, elles, contrôlées par le middleware save.scope.
 */
abstract class GameSaveActionRequest extends FormRequest
{
    /** Autorise la requête si l'utilisateur peut modifier la sauvegarde de la route (propriétaire ou admin). */
    public function authorize(): bool
    {
        $gameSave = $this->route('gameSave');

        return $gameSave instanceof GameSave
            && (bool) $this->user()?->can('update', $gameSave);
    }

    /** Sauvegarde ciblée par la route (déjà résolue par le route model binding). */
    protected function gameSave(): GameSave
    {
        return $this->route('gameSave');
    }
}

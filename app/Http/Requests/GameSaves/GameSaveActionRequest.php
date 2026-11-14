<?php

namespace App\Http\Requests\GameSaves;

use App\Models\GameSaves\GameSave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

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

    /**
     * Règle « existe DANS cette sauvegarde » pour un identifiant envoyé par le
     * navigateur (joueur, équipe…). Sans ce filtre, `exists` accepterait l'id
     * d'une autre partie et le cloisonnement reposerait sur chaque service.
     *
     * @param  string  $table  table portant une colonne game_save_id (game_players, game_teams…)
     */
    protected function existsInSave(string $table): Exists
    {
        return Rule::exists($table, 'id')->where('game_save_id', $this->gameSave()->id);
    }
}

<?php

namespace App\Http\Requests\GameSaves;

/** Requête de sélection d'un joueur par le manager humain pendant la draft. */
class PickDraftPlayerRequest extends GameSaveActionRequest
{
    /** Identifiant du joueur choisi dans le pool de la draft. */
    public function rules(): array
    {
        return [
            'player_id' => ['required', 'integer', $this->existsInSave('game_players')],
        ];
    }
}

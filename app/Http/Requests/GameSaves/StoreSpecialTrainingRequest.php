<?php

namespace App\Http\Requests\GameSaves;

/** Requête d'entraînement d'une technique spéciale pour un joueur. */
class StoreSpecialTrainingRequest extends GameSaveActionRequest
{
    /** Joueur, clé de la technique, ainsi que saison et semaine de l'entraînement. */
    public function rules(): array
    {
        return [
            'player_id' => ['required', 'integer', 'min:1'],
            'move_key'  => ['required', 'string', 'max:255'],
            'season'    => ['required', 'integer'],
            'week'      => ['required', 'integer'],
        ];
    }
}

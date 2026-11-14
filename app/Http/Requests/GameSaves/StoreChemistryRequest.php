<?php

namespace App\Http\Requests\GameSaves;

/** Requête de création d'un lien de complicité entre deux joueurs. */
class StoreChemistryRequest extends GameSaveActionRequest
{
    /** Les deux joueurs à associer ; ils doivent être différents. */
    public function rules(): array
    {
        return [
            'player_id'  => ['required', 'integer', $this->existsInSave('game_players')],
            'partner_id' => ['required', 'integer', 'different:player_id', $this->existsInSave('game_players')],
        ];
    }
}

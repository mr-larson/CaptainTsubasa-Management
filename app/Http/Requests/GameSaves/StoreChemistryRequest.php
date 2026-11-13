<?php

namespace App\Http\Requests\GameSaves;

/** Requête de création d'un lien de complicité entre deux joueurs. */
class StoreChemistryRequest extends GameSaveActionRequest
{
    /** Les deux joueurs à associer ; ils doivent être différents. */
    public function rules(): array
    {
        return [
            'player_id'  => ['required', 'integer', 'min:1'],
            'partner_id' => ['required', 'integer', 'min:1', 'different:player_id'],
        ];
    }
}

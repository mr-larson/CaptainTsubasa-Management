<?php

namespace App\Http\Requests\GameSaves;

class StoreChemistryRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'player_id'  => ['required', 'integer', 'min:1'],
            'partner_id' => ['required', 'integer', 'min:1', 'different:player_id'],
        ];
    }
}

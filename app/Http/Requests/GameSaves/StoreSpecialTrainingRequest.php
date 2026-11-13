<?php

namespace App\Http\Requests\GameSaves;

class StoreSpecialTrainingRequest extends GameSaveActionRequest
{
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

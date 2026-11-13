<?php

namespace App\Http\Requests\GameSaves;

class PickDraftPlayerRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'player_id' => ['required', 'integer'],
        ];
    }
}

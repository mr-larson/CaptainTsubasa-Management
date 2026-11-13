<?php

namespace App\Http\Requests\GameSaves;

class SubstitutePlayerRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'team_id'       => ['required', 'integer'],
            'starter_id'    => ['required', 'integer'],  // game_player_id qui sort
            'substitute_id' => ['required', 'integer'],  // game_player_id qui entre
        ];
    }
}

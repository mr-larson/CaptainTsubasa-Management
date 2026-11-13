<?php

namespace App\Http\Requests\GameSaves;

class ActivateBonusCardRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'target_player_id' => ['nullable', 'integer', 'exists:game_players,id'],
            'target_team_id'   => ['nullable', 'integer', 'exists:game_teams,id'],
        ];
    }
}

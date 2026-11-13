<?php

namespace App\Http\Requests\GameSaves;

use Illuminate\Validation\Rule;

class SignFreeAgentRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'team_id'       => ['required', 'integer', Rule::exists('game_teams', 'id')->where('game_save_id', $this->gameSave()->id)],
            'matches_total' => ['required', 'integer', 'min:1'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ];
    }
}

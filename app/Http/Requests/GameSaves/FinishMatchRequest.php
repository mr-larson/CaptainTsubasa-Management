<?php

namespace App\Http\Requests\GameSaves;

class FinishMatchRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'scoresByTeamId'   => ['required', 'array', 'min:2'],
            'scoresByTeamId.*' => ['integer', 'min:0'],
            'match_stats'      => ['nullable', 'array'],
            'foulEvents'       => ['nullable', 'array'],
        ];
    }
}

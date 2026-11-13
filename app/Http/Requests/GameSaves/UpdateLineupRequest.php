<?php

namespace App\Http\Requests\GameSaves;

use App\Helpers\FormationHelper;
use Illuminate\Validation\Rule;

class UpdateLineupRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'team_id'           => ['required', 'integer'],
            'slots'             => ['required', 'array', 'size:11'],
            'slots.*.slot'      => ['required', 'integer', 'between:1,11'],
            'slots.*.player_id' => ['nullable', 'integer'],
            'formation'         => ['nullable', 'string', Rule::in(array_keys(FormationHelper::SLOT_ZONES))],
        ];
    }
}

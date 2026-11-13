<?php

namespace App\Http\Requests\GameSaves;

use App\Helpers\FormationHelper;
use Illuminate\Validation\Rule;

class UpdateFormationRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'team_id'   => ['required', 'integer'],
            'formation' => ['required', 'string', Rule::in(array_keys(FormationHelper::SLOT_ZONES))],
        ];
    }
}

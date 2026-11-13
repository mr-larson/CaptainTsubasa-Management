<?php

namespace App\Http\Requests\GameSaves;

use App\Helpers\FormationHelper;
use Illuminate\Validation\Rule;

/** Requête de changement de formation d'une équipe. */
class UpdateFormationRequest extends GameSaveActionRequest
{
    /** Équipe et formation, qui doit faire partie de FormationHelper::SLOT_ZONES. */
    public function rules(): array
    {
        return [
            'team_id'   => ['required', 'integer'],
            'formation' => ['required', 'string', Rule::in(array_keys(FormationHelper::SLOT_ZONES))],
        ];
    }
}

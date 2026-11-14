<?php

namespace App\Http\Requests\GameSaves;

use App\Helpers\FormationHelper;
use Illuminate\Validation\Rule;

/** Requête de mise à jour de la composition : un joueur par poste, sur 11 postes. */
class UpdateLineupRequest extends GameSaveActionRequest
{
    /** Équipe, 11 postes (numéro + joueur éventuel) et formation facultative. */
    public function rules(): array
    {
        return [
            'team_id'           => ['required', 'integer', $this->existsInSave('game_teams')],
            'slots'             => ['required', 'array', 'size:11'],
            'slots.*.slot'      => ['required', 'integer', 'between:1,11', 'distinct'],
            // L'appartenance des joueurs à l'équipe est vérifiée par le contrôleur, en une requête.
            'slots.*.player_id' => ['nullable', 'integer', 'distinct'],
            'formation'         => ['nullable', 'string', Rule::in(array_keys(FormationHelper::SLOT_ZONES))],
        ];
    }
}

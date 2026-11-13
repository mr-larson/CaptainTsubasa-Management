<?php

namespace App\Http\Requests\GameSaves;

use Illuminate\Validation\Rule;

/** Requête de signature d'un joueur libre (agent libre) dans une équipe de la sauvegarde. */
class SignFreeAgentRequest extends GameSaveActionRequest
{
    /** Équipe d'accueil (limitée à la sauvegarde), nombre de matchs du contrat et motif facultatif. */
    public function rules(): array
    {
        return [
            'team_id'       => ['required', 'integer', Rule::exists('game_teams', 'id')->where('game_save_id', $this->gameSave()->id)],
            'matches_total' => ['required', 'integer', 'min:1'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ];
    }
}

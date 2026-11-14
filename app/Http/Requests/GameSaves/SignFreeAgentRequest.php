<?php

namespace App\Http\Requests\GameSaves;

/** Requête de signature d'un joueur libre (agent libre) dans une équipe de la sauvegarde. */
class SignFreeAgentRequest extends GameSaveActionRequest
{
    /** Équipe d'accueil (limitée à la sauvegarde), nombre de matchs du contrat et motif facultatif. */
    public function rules(): array
    {
        return [
            'team_id'       => ['required', 'integer', $this->existsInSave('game_teams')],
            'matches_total' => ['required', 'integer', 'min:1'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ];
    }
}

<?php

namespace App\Http\Requests\GameSaves;

/** Requête de fin de match : reçoit le résultat calculé par le moteur de match côté navigateur. */
class FinishMatchRequest extends GameSaveActionRequest
{
    /** Scores par id d'équipe (au moins deux), plus statistiques et événements de fautes facultatifs. */
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

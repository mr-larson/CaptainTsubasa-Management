<?php

namespace App\Http\Requests\GameSaves;

/** Requête de renouvellement d'un contrat de joueur. */
class RenewContractRequest extends GameSaveActionRequest
{
    /** Durée de la prolongation (en semaines) et semaine de fin du nouveau contrat. */
    public function rules(): array
    {
        return [
            'weeks'    => ['required', 'integer', 'min:1'],
            'end_week' => ['required', 'integer', 'min:1'],
        ];
    }
}

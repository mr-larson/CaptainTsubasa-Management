<?php

namespace App\Http\Requests\GameSaves;

/** Requête de changement du numéro de maillot d'un joueur. */
class UpdatePlayerNumberRequest extends GameSaveActionRequest
{
    /** Numéro de maillot compris entre 1 et 99. */
    public function rules(): array
    {
        return [
            'number' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}

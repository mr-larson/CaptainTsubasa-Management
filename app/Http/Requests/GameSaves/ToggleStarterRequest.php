<?php

namespace App\Http\Requests\GameSaves;

/** Requête de bascule titulaire/remplaçant d'un contrat. */
class ToggleStarterRequest extends GameSaveActionRequest
{
    /** Numéro de poste (1 à 11) facultatif à attribuer au titulaire. */
    public function rules(): array
    {
        return [
            'slot' => ['nullable', 'integer', 'between:1,11'],
        ];
    }
}

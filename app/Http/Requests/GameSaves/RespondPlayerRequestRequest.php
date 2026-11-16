<?php

namespace App\Http\Requests\GameSaves;

use Illuminate\Validation\Rule;

/** Requête de réponse de l'entraîneur à la demande d'un joueur. */
class RespondPlayerRequestRequest extends GameSaveActionRequest
{
    /** Réponse : accept / decline (congé, temps de jeu) ou rest / physio / play (souci physique). */
    public function rules(): array
    {
        return [
            'answer' => ['required', Rule::in(['accept', 'decline', 'rest', 'physio', 'play'])],
        ];
    }
}

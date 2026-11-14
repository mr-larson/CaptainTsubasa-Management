<?php

namespace App\Http\Requests\GameSaves;

/**
 * Requête de renommage d'une sauvegarde. Seul le libellé est modifiable par le
 * navigateur : semaine, saison, équipe et état sont pilotés par le serveur.
 */
class RenameGameSaveRequest extends GameSaveActionRequest
{
    /** Nouveau libellé de la partie (facultatif, 255 caractères au plus). */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }
}

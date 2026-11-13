<?php

namespace App\Http\Requests\GameSaves;

use App\Enums\PlayerPosition;
use Illuminate\Validation\Rules\Enum;

/** Requête de lancement d'un entraînement de poste (apprentissage d'un nouveau poste). */
class StartPositionTrainingRequest extends GameSaveActionRequest
{
    /** Joueur concerné et poste à apprendre (valeur de l'enum PlayerPosition). */
    public function rules(): array
    {
        return [
            'player_id' => ['required', 'integer'],
            'position'  => ['required', new Enum(PlayerPosition::class)],
        ];
    }
}

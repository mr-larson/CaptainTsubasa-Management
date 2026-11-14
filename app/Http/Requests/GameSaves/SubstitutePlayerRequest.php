<?php

namespace App\Http\Requests\GameSaves;

/** Requête de remplacement d'un titulaire par un remplaçant dans une équipe. */
class SubstitutePlayerRequest extends GameSaveActionRequest
{
    /** Équipe, titulaire qui sort et remplaçant qui entre (identifiants de game_players). */
    public function rules(): array
    {
        return [
            'team_id'       => ['required', 'integer', $this->existsInSave('game_teams')],
            'starter_id'    => ['required', 'integer', $this->existsInSave('game_players')],  // game_player_id qui sort
            'substitute_id' => ['required', 'integer', $this->existsInSave('game_players')],  // game_player_id qui entre
        ];
    }
}

<?php

namespace App\Http\Requests\GameSaves;

/** Requête d'activation d'une carte bonus/malus de l'équipe : cible éventuelle (joueur ou équipe) selon la carte. */
class ActivateBonusCardRequest extends GameSaveActionRequest
{
    /** Cible facultative : un joueur ou une équipe de la sauvegarde, selon le type de carte. */
    public function rules(): array
    {
        return [
            'target_player_id' => ['nullable', 'integer', 'exists:game_players,id'],
            'target_team_id'   => ['nullable', 'integer', 'exists:game_teams,id'],
        ];
    }
}

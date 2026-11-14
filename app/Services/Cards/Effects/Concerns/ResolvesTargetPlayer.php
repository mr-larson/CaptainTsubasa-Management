<?php

namespace App\Services\Cards\Effects\Concerns;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use Illuminate\Validation\ValidationException;

/** Récupère et valide le joueur cible d'une carte target=player (il doit être dans l'effectif de l'équipe propriétaire). */
trait ResolvesTargetPlayer
{
    protected function resolveTargetPlayer(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId): GamePlayer
    {
        if (!$targetPlayerId) {
            throw ValidationException::withMessages(['card' => 'Joueur cible requis.']);
        }

        $player = GamePlayer::query()
            ->where('game_save_id', $gameSave->id)
            ->where('id', $targetPlayerId)
            ->whereHas('contracts', fn ($q) => $q->where('game_team_id', $gameBonusCard->game_team_id))
            ->first();

        if (!$player) {
            throw ValidationException::withMessages(['card' => 'Ce joueur ne fait pas partie de votre effectif.']);
        }

        return $player;
    }
}

<?php

namespace App\Services\Cards\Effects;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;

/** Carte d'endurance : ajoute le montant de la carte (plafonné à 100) à tous les joueurs de l'équipe propriétaire, sauvegardés en base. */
class StaminaBoostEffect implements CardEffect
{
    public function apply(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId = null, ?int $targetTeamId = null): array
    {
        $amount  = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);
        $teamId  = $gameBonusCard->game_team_id;

        $players = GamePlayer::query()
            ->where('game_save_id', $gameSave->id)
            ->whereHas('contracts', fn ($q) => $q->where('game_team_id', $teamId))
            ->get();

        $updated = 0;
        foreach ($players as $player) {
            $player->stamina = min(100, ($player->stamina ?? 0) + $amount);
            $player->save();
            $updated++;
        }

        return ['message' => "+{$amount} stamina appliqué à {$updated} joueurs.", 'updated' => $updated];
    }
}

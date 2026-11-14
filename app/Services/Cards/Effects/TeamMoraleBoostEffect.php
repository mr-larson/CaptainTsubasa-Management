<?php

namespace App\Services\Cards\Effects;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;

/** Carte « Esprit d'équipe » : +moral à tout l'effectif. */
class TeamMoraleBoostEffect implements CardEffect
{
    public function apply(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId = null, ?int $targetTeamId = null): array
    {
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);
        $teamId = (int) $gameBonusCard->game_team_id;

        $players = GamePlayer::query()
            ->where('game_save_id', $gameSave->id)
            ->whereHas('contracts', fn ($q) => $q->where('game_team_id', $teamId))
            ->get();

        $updated = 0;
        foreach ($players as $player) {
            $player->morale = min(100, max(0, (int) ($player->morale ?? 0) + $amount));
            $player->save();
            $updated++;
        }

        return ['message' => "💛 +{$amount} de moral pour {$updated} joueur(s) de l'effectif.", 'updated' => $updated];
    }
}

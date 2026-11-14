<?php

namespace App\Services\Cards\Effects;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameSave;

/**
 * Les cartes pre_match ne s'appliquent pas immédiatement :
 * on les marque dans state.active_pre_match_cards pour que MatchSimulator les lise.
 */
class PreMatchCardEffect implements CardEffect
{
    public function apply(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId = null, ?int $targetTeamId = null): array
    {
        $gameSave->gameState()->addActivePreMatchCard([
            'game_bonus_card_id' => $gameBonusCard->id,
            'game_team_id'       => $gameBonusCard->game_team_id,
            'effect_type'        => $gameBonusCard->bonusCard->effect_type,
            'effect_value'       => $gameBonusCard->bonusCard->effect_value,
        ]);
        $gameSave->save();

        return ['message' => 'Carte activée pour le prochain match.'];
    }
}

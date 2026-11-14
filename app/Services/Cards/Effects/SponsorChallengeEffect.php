<?php

namespace App\Services\Cards\Effects;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameSave;

/**
 * Carte Sponsor : enregistre un défi à atteindre au prochain match.
 * La récompense est versée (ou non) lors de la résolution post-match (SponsorChallengeService).
 */
class SponsorChallengeEffect implements CardEffect
{
    public function apply(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId = null, ?int $targetTeamId = null): array
    {
        $card      = $gameBonusCard->bonusCard;
        $challenge = (string) ($card->effect_value['challenge'] ?? '');
        $reward    = (int) ($card->effect_value['reward'] ?? 0);

        $gameSave->gameState()->addSponsorChallenge([
            'game_bonus_card_id' => $gameBonusCard->id,
            'game_team_id'       => $gameBonusCard->game_team_id,
            'challenge'          => $challenge,
            'reward'             => $reward,
            'card_name'          => $card->name,
            'icon'               => $card->icon,
            'season'             => $gameSave->season,
            'week'               => $gameSave->week,
        ]);
        $gameSave->save();

        return ['message' => "Défi sponsor activé : {$card->name}. Récompense de {$reward} GL au prochain match si l'objectif est atteint."];
    }
}

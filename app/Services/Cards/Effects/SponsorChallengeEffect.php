<?php

namespace App\Services\Cards\Effects;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameSave;
use App\Services\Cards\MalusCardEffects;
use App\Services\Season\SeasonService;
use Illuminate\Validation\ValidationException;

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

        // « Prime de l'exploit » : ne se souscrit que contre un adversaire mieux classé.
        if ($challenge === 'upset') {
            $this->assertNextOpponentRankedAbove($gameSave, (int) $gameBonusCard->game_team_id);
        }

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

    /** Refuse l'activation si le prochain adversaire n'est pas classé devant l'équipe (ou s'il n'y a pas de match à venir). */
    private function assertNextOpponentRankedAbove(GameSave $gameSave, int $teamId): void
    {
        $opponentId = app(MalusCardEffects::class)->resolveNextOpponentTeamId($gameSave, $teamId);
        $ranks = app(SeasonService::class)->sortStandings($gameSave->gameTeams()->get())
            ->pluck('id')->map(fn ($id) => (int) $id)->flip();

        if (($ranks[$opponentId] ?? PHP_INT_MAX) >= ($ranks[$teamId] ?? 0)) {
            throw ValidationException::withMessages([
                'card' => 'Votre prochain adversaire n\'est pas mieux classé que vous : gardez cette carte pour un plus gros match.',
            ]);
        }
    }
}

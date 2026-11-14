<?php

namespace App\Services\Cards\Effects;

use App\Contracts\Randomizer;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Validation\ValidationException;

/**
 * Carte Pub : pari à résolution immédiate. Le gain est tiré au hasard entre
 * min et max (peut être négatif si la campagne fait un flop).
 */
class RevenueGambleEffect implements CardEffect
{
    public function __construct(private Randomizer $random)
    {
    }

    public function apply(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId = null, ?int $targetTeamId = null): array
    {
        $min = (int) ($gameBonusCard->bonusCard->effect_value['min'] ?? 0);
        $max = (int) ($gameBonusCard->bonusCard->effect_value['max'] ?? 0);
        if ($max < $min) {
            [$min, $max] = [$max, $min];
        }

        $gain = $this->random->int($min, $max);

        $team = GameTeam::find($gameBonusCard->game_team_id);
        if (!$team) {
            throw ValidationException::withMessages(['card' => 'Équipe introuvable.']);
        }

        $team->budget = max(0, ($team->budget ?? 0) + $gain);
        $team->save();

        $abs = abs($gain);
        $msg = $gain >= 0
            ? "📈 La campagne a payé : +{$abs} GL au budget (désormais {$team->budget} GL)."
            : "📉 La campagne a fait un flop : −{$abs} GL sur le budget (désormais {$team->budget} GL).";

        return ['message' => $msg, 'gain' => $gain, 'new_budget' => $team->budget];
    }
}

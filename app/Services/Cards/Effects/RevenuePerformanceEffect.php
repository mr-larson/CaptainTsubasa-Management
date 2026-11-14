<?php

namespace App\Services\Cards\Effects;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Validation\ValidationException;

/**
 * Carte « Vente de maillots » : revenu indexé sur le dernier match joué de
 * l'équipe (bonus par but marqué + prime de victoire). Récompense la forme.
 */
class RevenuePerformanceEffect implements CardEffect
{
    public function apply(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId = null, ?int $targetTeamId = null): array
    {
        $ev      = $gameBonusCard->bonusCard->effect_value ?? [];
        $base    = (int) ($ev['base'] ?? 200);
        $perGoal = (int) ($ev['per_goal'] ?? 150);
        $winBon  = (int) ($ev['win_bonus'] ?? 500);

        $teamId = (int) $gameBonusCard->game_team_id;
        $team   = GameTeam::find($teamId);
        if (!$team) {
            throw ValidationException::withMessages(['card' => 'Équipe introuvable.']);
        }

        $match = GameMatch::where('game_save_id', $gameSave->id)
            ->where('status', 'played')
            ->where(fn ($q) => $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId))
            ->orderByDesc('week')
            ->first();

        $scored = 0; $won = false;
        if ($match) {
            $isHome  = (int) $match->home_team_id === $teamId;
            $scored  = (int) ($isHome ? $match->home_score : $match->away_score);
            $against = (int) ($isHome ? $match->away_score : $match->home_score);
            $won     = $scored > $against;
        }

        $gain = $base + $scored * $perGoal + ($won ? $winBon : 0);
        $team->budget = ($team->budget ?? 0) + $gain;
        $team->save();

        $detail = $match
            ? "(dernier match : {$scored} but(s)" . ($won ? ', victoire' : '') . ')'
            : '(aucun match joué récemment)';

        return ['message' => "👕 Vente de maillots : +{$gain} GL au budget {$detail}.", 'gain' => $gain];
    }
}

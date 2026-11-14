<?php

namespace App\Services\Cards\Effects;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameSave;
use Illuminate\Validation\ValidationException;

/** Carte blessure : raccourcit de quelques semaines la blessure en cours du joueur cible, ou la guérit immédiatement pour 'injury_cure'. Lève une erreur de validation si le joueur n'est pas blessé. */
class InjuryReduceEffect implements CardEffect
{
    public function apply(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId = null, ?int $targetTeamId = null): array
    {
        if (!$targetPlayerId) {
            throw ValidationException::withMessages(['card' => 'Joueur cible requis.']);
        }

        $weeks = (int) ($gameBonusCard->bonusCard->effect_value['weeks'] ?? 2);
        $isCure = $gameBonusCard->bonusCard->effect_type === 'injury_cure';

        $injury = GameInjury::query()
            ->where('game_save_id', $gameSave->id)
            ->where('game_player_id', $targetPlayerId)
            ->where('week_return', '>', $gameSave->week)
            ->orderByDesc('week_return')
            ->first();

        if (!$injury) {
            throw ValidationException::withMessages(['card' => 'Ce joueur n\'est pas blessé.']);
        }

        if ($isCure) {
            $injury->week_return = $gameSave->week; // retour immédiat
        } else {
            $injury->week_return = max($gameSave->week, $injury->week_return - $weeks);
        }
        $injury->save();

        $msg = $isCure
            ? 'Joueur guéri immédiatement !'
            : "Retour avancé de {$weeks} semaine(s). Retour semaine {$injury->week_return}.";

        return ['message' => $msg, 'week_return' => $injury->week_return];
    }
}

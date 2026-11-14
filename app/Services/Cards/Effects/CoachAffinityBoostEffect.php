<?php

namespace App\Services\Cards\Effects;

use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameSave;
use App\Services\Cards\Effects\Concerns\ResolvesTargetPlayer;
use App\Services\Concerns\LogsCoachAffinity;

/** Carte de relation avec le coach : augmente l'affinité du joueur cible (bornée -100 à 100), sauvegarde et inscrit l'évolution dans le journal d'affinité. */
class CoachAffinityBoostEffect implements CardEffect
{
    use LogsCoachAffinity;
    use ResolvesTargetPlayer;

    public function apply(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId = null, ?int $targetTeamId = null): array
    {
        $player = $this->resolveTargetPlayer($gameSave, $gameBonusCard, $targetPlayerId);
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);

        $before                 = (int) ($player->coach_affinity ?? 0);
        $player->coach_affinity = min(100, max(-100, $before + $amount));
        $player->save();

        $gain = $player->coach_affinity - $before;
        $this->logCoachAffinity($gameSave, $player, $gain, $gameBonusCard->bonusCard->name);

        return [
            'message'            => "+{$gain} de relation avec le coach pour {$player->lastname} (désormais {$player->coach_affinity}).",
            'new_coach_affinity' => $player->coach_affinity,
        ];
    }
}

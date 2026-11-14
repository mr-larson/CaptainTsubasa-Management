<?php

namespace App\Services\Cards\Effects;

use App\Enums\MoraleSource;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Models\GameSaves\GameSave;
use App\Services\Cards\Effects\Concerns\ResolvesTargetPlayer;

/** Carte de moral individuelle : augmente le moral du joueur cible (borné 0-100), sauvegarde et inscrit une ligne dans le journal de moral. */
class MoraleBoostEffect implements CardEffect
{
    use ResolvesTargetPlayer;

    public function apply(GameSave $gameSave, GameBonusCard $gameBonusCard, ?int $targetPlayerId = null, ?int $targetTeamId = null): array
    {
        $player = $this->resolveTargetPlayer($gameSave, $gameBonusCard, $targetPlayerId);
        $amount = (int) ($gameBonusCard->bonusCard->effect_value['amount'] ?? 0);

        $before          = (int) ($player->morale ?? 0);
        $player->morale  = min(100, max(0, $before + $amount));
        $player->save();

        $gain = $player->morale - $before;

        GamePlayerMoraleLog::create([
            'game_save_id'   => $gameSave->id,
            'game_player_id' => $player->id,
            'source'         => MoraleSource::BonusCard,
            'value'          => $gain,
            'label'          => $gameBonusCard->bonusCard->name,
            'week'           => $gameSave->week,
            'season'         => $gameSave->season,
        ]);

        return [
            'message'    => "+{$gain} de moral pour {$player->lastname} (désormais {$player->morale}).",
            'new_morale' => $player->morale,
        ];
    }
}

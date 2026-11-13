<?php

namespace App\Services\Concerns;

use App\Enums\MoraleSource;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Models\GameSaves\GameSave;

/** Journalise une variation d'affinité joueur/coach (partagé par les cartes bonus et malus). */
trait LogsCoachAffinity
{
    protected function logCoachAffinity(GameSave $save, GamePlayer $player, int $delta, string $label): void
    {
        if ($delta === 0) return;
        GamePlayerMoraleLog::create([
            'game_save_id' => $save->id, 'game_player_id' => $player->id,
            'source' => MoraleSource::CoachAffinity, 'value' => $delta, 'label' => $label,
            'week' => $save->week, 'season' => $save->season,
        ]);
    }

}

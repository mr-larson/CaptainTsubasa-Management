<?php

namespace App\Models\GameSaves;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameInjury extends Model
{
    protected $fillable = [
        'game_save_id',
        'game_player_id',
        'game_match_id',
        'severity',
        'weeks_out',
        'week_injured',
        'week_return',
        'description',
    ];

    public function gameSave(): BelongsTo
    {
        return $this->belongsTo(GameSave::class);
    }

    public function gamePlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class);
    }

    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class);
    }

    public function isActive(int $currentWeek): bool
    {
        return $currentWeek < $this->week_return;
    }
}

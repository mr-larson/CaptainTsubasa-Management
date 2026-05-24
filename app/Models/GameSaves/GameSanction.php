<?php

namespace App\Models\GameSaves;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSanction extends Model
{
    protected $fillable = [
        'game_save_id',
        'game_player_id',
        'game_match_id',
        'type',
        'weeks_suspended',
        'week_match',
        'week_return',
        'yellow_card_count',
    ];

    protected $casts = [
        'weeks_suspended'   => 'integer',
        'week_match'        => 'integer',
        'week_return'       => 'integer',
        'yellow_card_count' => 'integer',
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

    public function isSuspension(): bool
    {
        return in_array($this->type, ['red', 'double_yellow']);
    }
}

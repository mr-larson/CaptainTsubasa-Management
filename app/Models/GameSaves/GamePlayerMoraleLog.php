<?php

namespace App\Models\GameSaves;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePlayerMoraleLog extends Model
{
    protected $fillable = [
        'game_save_id',
        'game_player_id',
        'source',
        'value',
        'label',
        'week',
        'season',
    ];

    protected $casts = [
        'value'  => 'integer',
        'week'   => 'integer',
        'season' => 'integer',
    ];

    public function gameSave(): BelongsTo
    {
        return $this->belongsTo(GameSave::class);
    }

    public function gamePlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class);
    }
}

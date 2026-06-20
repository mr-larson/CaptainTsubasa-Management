<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameDeclaration extends Model
{
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id',
        'game_player_id',
        'type',
        'deserved',
        'outcome',
        'affinity_delta',
        'morale_delta',
        'week',
        'season',
    ];

    protected $casts = [
        'deserved'       => 'boolean',
        'affinity_delta' => 'integer',
        'morale_delta'   => 'integer',
        'week'           => 'integer',
        'season'         => 'integer',
    ];

    public function gamePlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class);
    }
}

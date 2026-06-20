<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePromise extends Model
{
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id',
        'game_player_id',
        'game_team_id',
        'type',
        'start_week',
        'due_week',
        'target_matches',
        'played_matches',
        'target_turns',
        'played_turns',
        'status',
        'season',
    ];

    protected $casts = [
        'start_week'     => 'integer',
        'due_week'       => 'integer',
        'target_matches' => 'integer',
        'played_matches' => 'integer',
        'target_turns'   => 'integer',
        'played_turns'   => 'integer',
        'season'         => 'integer',
    ];

    public function gamePlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class);
    }

    public function gameTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class);
    }
}

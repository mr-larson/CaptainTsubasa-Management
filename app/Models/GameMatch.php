<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_save_id',
        'week',
        'home_team_id',
        'away_team_id',
        'status',
        'home_score',
        'away_score',
    ];

    public function gameSave(): BelongsTo
    {
        return $this->belongsTo(GameSave::class);
    }


    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class, 'away_team_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_save_id',
        'game_team_id',
        'game_player_id',
        'salary',
        'start_week',
        'end_week',
        'is_starter',
    ];
    protected $casts = [
        'salary'     => 'integer',
        'start_week' => 'integer',
        'end_week'   => 'integer',
        'is_starter' => 'boolean',
    ];


    public function gameSave()
    {
        return $this->belongsTo(GameSave::class);
    }

    public function gameTeam()
    {
        return $this->belongsTo(GameTeam::class, 'game_team_id');
    }

    public function gamePlayer()
    {
        return $this->belongsTo(GamePlayer::class, 'game_player_id');
    }


    public function isActive(int $currentWeek): bool
    {
        if ($this->end_week !== null && $currentWeek > $this->end_week) {
            return false;
        }

        return $currentWeek >= $this->start_week;
    }

}

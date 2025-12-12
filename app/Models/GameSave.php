<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'controlled_game_team_id',
        'control_mode',
        'period',
        'season',
        'week',
        'label',
        'state',
    ];


    protected $casts = [
        'state' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function matches()
    {
        return $this->hasMany(GameMatch::class);
    }

    public function gameTeams()
    {
        return $this->hasMany(GameTeam::class);
    }

    public function gamePlayers()
    {
        return $this->hasMany(GamePlayer::class);
    }

    public function gameContracts()
    {
        return $this->hasMany(GameContract::class);
    }

    public function controlledGameTeam()
    {
        return $this->belongsTo(GameTeam::class, 'controlled_game_team_id');
    }
}

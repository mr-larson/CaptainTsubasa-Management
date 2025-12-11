<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_save_id',
        'base_team_id',
        'name',
        'description',
        'budget',
        'wins',
        'draws',
        'losses',
    ];

    public function gameSave()
    {
        return $this->belongsTo(GameSave::class);
    }

    public function baseTeam()
    {
        return $this->belongsTo(Team::class, 'base_team_id');
    }

    public function contracts()
    {
        return $this->hasMany(GameContract::class);
    }

    public function players()
    {
        // via les contrats
        return $this->hasManyThrough(
            GamePlayer::class,
            GameContract::class,
            'game_team_id',   // fk sur contracts
            'id',             // pk de game_players
            'id',             // pk de game_teams
            'game_player_id'  // fk sur contracts
        );
    }
}

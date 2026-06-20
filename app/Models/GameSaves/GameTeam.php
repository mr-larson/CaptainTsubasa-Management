<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameTeam extends Model
{
    use HasFactory;
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id',
        'base_team_id',
        'is_controlled',
        'human_seat',
        'name',
        'description',
        'budget',
        'wins',
        'draws',
        'losses',
        'goals_for',
        'goals_against',
        'logo_path',
        'formation',
        'tactical_style',
        'management_philosophy',
    ];

    protected $casts = [
        'is_controlled' => 'boolean',
        'human_seat'    => 'integer',
    ];

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

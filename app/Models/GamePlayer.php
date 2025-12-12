<?php

namespace App\Models;

use App\Models\Traits\HasSoccerStats;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GamePlayer extends Model
{
    use HasFactory;
    use HasSoccerStats;

    protected $fillable = [
        'game_save_id',
        'base_player_id',
        'firstname',
        'lastname',
        'position',
        'speed',
        'stamina',
        'attack',
        'defense',
        'shot',
        'pass',
        'dribble',
        'block',
        'intercept',
        'tackle',
        'hand_save',
        'punch_save',
        'cost',
    ];

    public function gameSave()
    {
        return $this->belongsTo(GameSave::class);
    }

    public function basePlayer()
    {
        return $this->belongsTo(Player::class, 'base_player_id');
    }

    // (optionnel mais conseillé) : contrats dans la partie
    public function contracts()
    {
        return $this->hasMany(GameContract::class, 'game_player_id');
    }

    /**
     * Implémentation pour HasSoccerStats :
     * les stats sont des colonnes directement sur la table game_players.
     */
    protected function getBaseStat(string $key): int
    {
        return (int) ($this->{$key} ?? 0);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }
}

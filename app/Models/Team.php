<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Team extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'budget',
        'points',
        'wins',
        'draws',
        'losses',
    ];

    public function players()
    {
        return $this->belongsToMany(Player::class, 'contracts');
    }

    // Relation avec SoccerMatch (équipe à domicile)
    public function homeMatches()
    {
        return $this->hasMany(SoccerMatch::class, 'team_id_home');
    }

    // Relation avec SoccerMatch (équipe visiteuse)
    public function awayMatches()
    {
        return $this->hasMany(SoccerMatch::class, 'team_id_away');
    }
}

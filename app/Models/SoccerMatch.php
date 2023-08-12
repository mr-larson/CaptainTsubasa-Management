<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoccerMatch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'team_a_id',
        'team_b_id',
        'score_team_a',
        'score_team_b',
        'match_statistics',
        'weather',
        'red_cards',
        'yellow_cards',
        'team_a_players',
        'team_b_players',
        'match_date',
        'highlights',
        //... autres champs selon vos besoins...
    ];

    public function teamA()
    {
        return $this->belongsTo(Team::class, 'team_a_id');
    }

    public function teamB()
    {
        return $this->belongsTo(Team::class, 'team_b_id');
    }

    // Si vous souhaitez récupérer les joueurs ayant reçu des cartons rouges ou jaunes, vous pouvez ajouter des méthodes comme celle-ci
    public function getRedCardPlayersAttribute()
    {
        return Player::whereIn('id', $this->red_cards ?? [])->get();
    }

    public function getYellowCardPlayersAttribute()
    {
        return Player::whereIn('id', $this->yellow_cards ?? [])->get();
    }

}

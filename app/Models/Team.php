<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name', //string, nom de l'équipe
        'logo_path', //string, chemin vers le logo de l'équipe
        'budget', //integer, budget de l'équipe
        'points', //integer, points de l'équipe
        'wins', //integer, victoires de l'équipe
        'draws', //integer, matchs nuls de l'équipe
        'losses', //integer, défaites de l'équipe
        'team_stats_bonus', //json, bonus d'équipe
        'active_cards', //json, cartes actives
        'description', //string, description de l'équipe
    ];

    protected $casts = [
        'team_stats_bonus' => 'array',
    ];

    public function players()
    {
        return $this->hasMany(Player::class, 'current_team_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function matchesAsTeamA()
    {
        return $this->hasMany(SoccerMatch::class, 'team_a_id');
    }

    public function matchesAsTeamB()
    {
        return $this->hasMany(SoccerMatch::class, 'team_b_id');
    }

    // Combinez les matchs de l'équipe A et B pour obtenir tous les matchs de l'équipe
    public function matches()
    {
        return $this->matchesAsTeamA->concat($this->matchesAsTeamB);
    }

    public function totalYellowCards()
    {
        return $this->matches->sum(function ($match) {
            return count($match->yellow_cards); // Assurez-vous que yellow_cards est un tableau
        });
    }

    public function totalRedCards()
    {
        return $this->matches->sum(function ($match) {
            return count($match->red_cards); // Assurez-vous que red_cards est un tableau
        });
    }

}

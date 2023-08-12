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
        'name',
        'logo_path',
        'budget',
        'points',
        'wins',
        'draws',
        'losses',
        'team_stats_bonus',
        //... autres champs selon vos besoins...
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

    // Pour obtenir le nombre total de cartes jaunes pour cette équipe
    public function totalYellowCards()
    {
        // À implémenter : Parcourez tous les matchs de l'équipe et sommez le nombre de cartes jaunes
    }

    // Pour obtenir le nombre total de cartes rouges pour cette équipe
    public function totalRedCards()
    {
        // À implémenter : Parcourez tous les matchs de l'équipe et sommez le nombre de cartes rouges
    }
}

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
        'team_a_id', //unsignedBigInteger, équipe A
        'team_b_id', //unsignedBigInteger, équipe B
        'score_team_a', //unsignedInteger, score de l'équipe A
        'score_team_b', //unsignedInteger, score de l'équipe B
        'match_statistics', //json, array, statistiques du match
        'weather', //string, météo
        'red_cards', //json, array, cartons rouges (liste d'IDs de joueurs)
        'yellow_cards', //json, array, cartons jaunes (liste d'IDs de joueurs)
        'team_a_players', //json, array, liste d'IDs de joueurs de l'équipe A
        'team_b_players', //json, array, liste d'IDs de joueurs de l'équipe B
        'match_date', //date, date du match
        'highlights', //text, résumé ou moments forts du match
        'team_a_promo_cards', //json, array, cartes promotionnelles de l'équipe A 
        'team_b_promo_cards', //json, array, cartes promotionnelles de l'équipe B
        'team_a_pre_match_fatigue', //json, array, fatigue des joueurs de l'équipe A avant le match
        'team_b_pre_match_fatigue', //json, array, fatigue des joueurs de l'équipe B avant le match
        'team_a_post_match_fatigue', //json, array, fatigue des joueurs de l'équipe A après le match
        'team_b_post_match_fatigue', //json, array, fatigue des joueurs de l'équipe B après le match
        'injured_players', //json, array, joueurs blessés
        'team_a_financial_gain', //decimal, gain financier de l'équipe A
        'team_b_financial_gain', //decimal, gain financier de l'équipe B
    ];

    protected $casts = [
        'match_statistics' => 'array',
        'red_cards' => 'array',
        'yellow_cards' => 'array',
        'team_a_players' => 'array',
        'team_b_players' => 'array',
        'highlights' => 'array',
        'team_a_promo_cards' => 'array',
        'team_b_promo_cards' => 'array',
        'team_a_pre_match_fatigue' => 'array',
        'team_b_pre_match_fatigue' => 'array',
        'team_a_post_match_fatigue' => 'array',
        'team_b_post_match_fatigue' => 'array',
        'injured_players' => 'array',
    ];

    // Relation vers les équipes
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

    /**
     * Distribuez les récompenses financières aux équipes après le match.
     */
    public function distributeFinancialRewards()
    {
        $this->teamA->budget += $this->team_a_financial_gain;
        $this->teamA->save();

        $this->teamB->budget += $this->team_b_financial_gain;
        $this->teamB->save();
    }


}

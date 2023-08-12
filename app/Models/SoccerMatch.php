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
        'team_a_promo_cards',
        'team_b_promo_cards',
        'team_a_pre_match_fatigue',
        'team_b_pre_match_fatigue',
        'team_a_post_match_fatigue',
        'team_b_post_match_fatigue',
        'injured_players',
        'team_a_financial_gain',
        'team_b_financial_gain',
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

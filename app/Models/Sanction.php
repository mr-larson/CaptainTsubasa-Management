<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sanction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'player_id',
        'match_id',
        'type',
        'duration',
        // ... autres champs selon vos besoins...
    ];

    /**
     * Relation vers le joueur.
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Relation vers le match.
     */
    public function match()
    {
        return $this->belongsTo(SoccerMatch::class, 'match_id');  // Notez que nous utilisons SoccerMatch si c'est le nom que vous avez donné à votre modèle de match.
    }

    /**
     * Méthode pour déterminer si une sanction est toujours active.
     */
    public function isActive()
    {
        $lastAffectedMatch = $this->match->match_date->addWeeks($this->duration);
        return now()->lessThan($lastAffectedMatch);
    }
}

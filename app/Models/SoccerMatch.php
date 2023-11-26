<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoccerMatch extends Model
{
    use HasFactory;
    use SoftDeletes;

    // Attributs assignables en masse
    protected $fillable = ['team_id_home', 'team_id_away', 'score_home', 'score_away', 'date'];

    // Relation avec Team (équipe à domicile)
    public function teamHome()
    {
        return $this->belongsTo(Team::class, 'team_id_home');
    }

    // Relation avec Team (équipe visiteuse)
    public function teamAway()
    {
        return $this->belongsTo(Team::class, 'team_id_away');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoccerMatch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'team_id_home',
        'team_id_away',
        'score_team_home',
        'score_team_away',
        'date',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id_home');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id_away');
    }
}

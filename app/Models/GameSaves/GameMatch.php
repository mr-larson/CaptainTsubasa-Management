<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMatch extends Model
{
    use HasFactory;
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id',
        'week',
        'home_team_id',
        'away_team_id',
        'status',
        'round',
        'home_score',
        'away_score',
        'match_stats'
    ];

    protected $casts = [
        'match_stats' => 'array',
        // Colonne calculée (select de la page Play) : détail disponible ou non.
        'has_stats'   => 'boolean',
    ];

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class, 'away_team_id');
    }
}

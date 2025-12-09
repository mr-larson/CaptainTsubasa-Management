<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'contracts';

    protected $fillable = [
        'team_id',
        'player_id',
        'salary',          // devient "cost_per_match"
        'matches_total',
        'matches_played',
        'start_date',      // gardées mais plus utilisées pour le gameplay
        'end_date',
    ];

    protected $casts = [
        'start_date'     => 'date',
        'end_date'       => 'date',
        'matches_total'  => 'integer',
        'matches_played' => 'integer',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    // --- Helpers gameplay ---

    /**
     * Matchs restants avant fin de contrat.
     */
    public function getMatchesRemainingAttribute(): int
    {
        return max(0, ($this->matches_total ?? 0) - ($this->matches_played ?? 0));
    }

    /**
     * Le contrat est-il expiré (en termes de matchs) ?
     */
    public function isExpired(): bool
    {
        return ($this->matches_played ?? 0) >= ($this->matches_total ?? 0);
    }

    /**
     * Contrat en cours (non expiré).
     */
    public function isCurrent(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Ancienne méthode si tu l’utilisais (basée sur dates).
     * Tu peux soit la retirer, soit la laisser obsolète.
     */
    public function hasEnded(): bool
    {
        return $this->isExpired();
    }
}

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
        'salary',
        'matches_total',
        'matches_played',
        'start_date',
        'end_date',
        'is_starter',
        'is_captain',
    ];

    protected $casts = [
        'start_date'     => 'date',
        'end_date'       => 'date',
        'matches_total'  => 'integer',
        'matches_played' => 'integer',
        'is_starter'     => 'boolean',
        'is_captain'     => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function getMatchesRemainingAttribute(): int
    {
        return max(0, ($this->matches_total ?? 0) - ($this->matches_played ?? 0));
    }

    public function isExpired(): bool
    {
        return ($this->matches_played ?? 0) >= ($this->matches_total ?? 0);
    }

    public function isCurrent(): bool
    {
        return ! $this->isExpired();
    }

    public function hasEnded(): bool
    {
        return $this->isExpired();
    }
}

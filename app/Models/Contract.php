<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $team_id
 * @property int $player_id
 * @property int $salary
 * @property string $start_date
 * @property string $end_date
 **/

class Contract extends Model
{
    use HasFactory;
    Use SoftDeletes;

    protected $table = 'contracts';

    protected $fillable = [
        'team_id',
        'player_id',
        'salary',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function isCurrent(): bool
    {
        return $this->start_date <= today() && $this->end_date >= today();
    }

    public function hasEnded(): bool
    {
        return $this->end_date < today();
    }
}

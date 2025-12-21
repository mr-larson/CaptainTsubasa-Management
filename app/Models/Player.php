<?php

namespace App\Models;

use App\Enums\PlayerPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasSoccerStats;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasSoccerStats;

    protected $table = 'players';

    /**
     * Stats par défaut pour tous les joueurs.
     * Servent de filet de sécurité si une clé manque dans le JSON.
     */
    public const DEFAULT_STATS = [
        'speed'      => 50,
        'stamina'    => 50,
        'attack'     => 50,
        'defense'    => 50,

        'shot'       => 50,
        'pass'       => 50,
        'dribble'    => 50,
        'block'      => 50,
        'intercept'  => 50,
        'tackle'     => 50,

        'hand_save'  => 0,
        'punch_save' => 0,
    ];

    protected $fillable = [
        'firstname',
        'lastname',
        'age',
        'position',
        'cost',
        'stats',
        'description',
        'photo_path',
    ];

    protected $casts = [
        'stats'    => 'array',
        'position' => PlayerPosition::class,
    ];

    // ==========================
    //  RELATIONS
    // ==========================

    public function teams(): BelongsToMany
    {
        return $this
            ->belongsToMany(Team::class, 'contracts')
            ->withPivot(['salary', 'start_date', 'end_date'])
            ->withTimestamps();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    // ==========================
    //  ACCESSORS
    // ==========================

    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }

    public function getStatsAttribute($value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true) ?: [];
        }

        if (! is_array($value)) {
            $value = [];
        }

        return array_merge(self::DEFAULT_STATS, $value);
    }


    public function setStatsAttribute($value): void
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        }

        if (! is_array($value)) {
            $value = [];
        }

        $merged = array_merge(self::DEFAULT_STATS, $value);

        $this->attributes['stats'] = json_encode($merged);
    }


    // ==========================
    //  HELPERS POUR LE MOTEUR
    // ==========================

    protected function getBaseStat(string $key): int
    {
        $stats = $this->stats;

        return (int) ($stats[$key] ?? self::DEFAULT_STATS[$key] ?? 0);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }

        return asset('storage/' . ltrim($this->photo_path, '/'));
    }

}

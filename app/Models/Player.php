<?php

namespace App\Models;

use App\Enums\PlayerPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Player
 *
 * @property int         $id
 * @property string      $firstname
 * @property string      $lastname
 * @property int         $age
 * @property string      $position
 * @property int         $cost
 * @property array       $stats
 * @property string|null $description
 *
 * @property-read string $full_name
 */
class Player extends Model
{
    use HasFactory;
    use SoftDeletes;

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

        'hand_save'  => 0,    // gardien
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
    //  ACCESSORS / MUTATORS
    // ==========================

    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }

    /**
     * Retourne les stats en fusionnant avec les valeurs par défaut.
     */
    public function getStatsAttribute($value): array
    {
        $stats = $value ?? [];

        if (is_string($stats)) {
            $stats = json_decode($stats, true) ?: [];
        }

        return array_merge(self::DEFAULT_STATS, $stats);
    }

    /**
     * Force le stockage des stats comme JSON complet + merge avec defaults.
     */
    public function setStatsAttribute($value): void
    {
        if (! is_array($value)) {
            $value = [];
        }

        $this->attributes['stats'] = json_encode(
            array_merge(self::DEFAULT_STATS, $value)
        );
    }

    // ==========================
    //  HELPERS POUR LE MOTEUR
    // ==========================

    public function shotStat(): int
    {
        return (int) $this->stats['shot'];
    }

    public function passStat(): int
    {
        return (int) $this->stats['pass'];
    }

    public function dribbleStat(): int
    {
        return (int) $this->stats['dribble'];
    }

    public function blockStat(): int
    {
        return (int) $this->stats['block'];
    }

    public function interceptionStat(): int
    {
        return (int) $this->stats['intercept'];
    }

    public function tackleStat(): int
    {
        return (int) $this->stats['tackle'];
    }

    public function gkCatchStat(): int
    {
        return (int) $this->stats['hand_save'];
    }

    public function gkPunchStat(): int
    {
        return (int) $this->stats['punch_save'];
    }

    /**
     * Stat utilisée pour les "special" offensifs.
     */
    public function offensiveSpecialStat(): int
    {
        return (int) $this->stats['attack'];
    }

    /**
     * Stat utilisée pour les "special" défensifs.
     */
    public function defensiveSpecialStat(): int
    {
        return (int) $this->stats['defense'];
    }
}

<?php

namespace App\Models;

use App\Enums\PlayerPosition;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasFullName;
use App\Models\Traits\HasPhotoUrl;
use App\Models\Traits\HasSoccerStats;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasSoccerStats;
    use HasFullName;
    use HasPhotoUrl;

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
        'heading'    => 15,

        'hand_save'  => 0,
        'punch_save' => 0,
    ];

    protected $fillable = [
        'firstname',
        'lastname',
        'age',
        'position',
        'secondary_positions',
        'cost',
        'stats',
        'special_moves',
        'description',
        'photo_path',
    ];

    protected $casts = [
        'position' => PlayerPosition::class,
        'secondary_positions' => 'array',
        'special_moves' => 'array'
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

    /**
     * Stats stockées en JSON, toujours fusionnées avec DEFAULT_STATS
     * pour garantir la présence de toutes les clés (lecture comme écriture).
     * Source unique : pas de cast 'array' en plus, sinon double encodage.
     */
    protected function stats(): Attribute
    {
        $normalize = static fn ($value): array => array_merge(
            self::DEFAULT_STATS,
            is_array($value) ? $value : (json_decode((string) $value, true) ?: [])
        );

        return Attribute::make(
            get: fn ($value) => $normalize($value),
            set: fn ($value) => json_encode($normalize($value)),
        );
    }


    // ==========================
    //  HELPERS POUR LE MOTEUR
    // ==========================

    protected function getBaseStat(string $key): int
    {
        $stats = $this->stats;

        return (int) ($stats[$key] ?? self::DEFAULT_STATS[$key] ?? 0);
    }

}

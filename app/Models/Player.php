<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $firstname
 * @property string $lastname
 * @property int $age
 * @property string $position
 * @property int $cost
 * @property array $stats
 * @property string $description
 **/
class Player extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'players';

    protected $fillable = [
        'firstname',
        'lastname',
        'age',
        'position',
        'cost',
        'stats',
        'description'
    ];

    protected $casts = [
        'stats' => 'array',
    ];

    // Relation avec Team via la table pivot Contract
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'contracts');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }
}

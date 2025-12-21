<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Team
 *
 * @property int    $id
 * @property string $name
 * @property string|null $description
 * @property int    $budget
 * @property int    $wins
 * @property int    $draws
 * @property int    $losses
 */
class Team extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'teams';

    protected $fillable = [
        'name',
        'description',
        'budget',
        'wins',
        'draws',
        'losses',
        'logo_path',
    ];

    /**
     * Joueurs liés via la table pivot contracts.
     */
    public function players(): BelongsToMany
    {
        return $this
            ->belongsToMany(Player::class, 'contracts')
            ->withPivot(['salary', 'start_date', 'end_date'])
            ->withTimestamps();
    }

    public function contracts() : HasMany
    {
        return $this->hasMany(Contract::class);
    }


    /**
     * Matchs joués à domicile.
     */
    public function homeMatches(): HasMany
    {
        return $this->hasMany(SoccerMatch::class, 'team_id_home');
    }

    /**
     * Matchs joués à l'extérieur.
     */
    public function awayMatches(): HasMany
    {
        return $this->hasMany(SoccerMatch::class, 'team_id_away');
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null; // ou une image par défaut si tu veux
        }

        // logo_path stocke un chemin type: images/teams/nankatsu.webp
        return asset($this->logo_path);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sanction extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Les attributs que vous pouvez affecter massivement.
     *
     * @var array
     */
    protected $fillable = [
        'player_id',
        'match_id',
        'type',
        'duration', // en semaines
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'duration' => 'integer',
    ];

    /**
     * Boot function from Laravel.
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($sanction) {
            if ($sanction->duration < 1 || $sanction->duration > 6) {
                throw new \InvalidArgumentException('La durée de la sanction doit être comprise entre 1 et 6 semaines.');
            }
        });
    }

    /**
     * Relation vers le joueur.
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Relation vers le match.
     */
    public function match()
    {
        return $this->belongsTo(SoccerMatch::class, 'match_id');
    }

    /**
     * Méthode pour déterminer si une sanction est toujours active.
     */
    public function isActive()
    {
        $lastAffectedMatch = $this->match->match_date->addWeeks($this->duration);
        return now()->lessThan($lastAffectedMatch);
    }

    /**
     * Retourne la durée de la sanction en jours.
     *
     * @return int
     */
    public function getDurationInDays()
    {
        return $this->duration * 7;
    }
}

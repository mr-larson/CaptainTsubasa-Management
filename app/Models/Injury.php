<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Injury extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'player_id', //foreign key vers le joueur
        'description', //string
        'duration_in_days', //integer
        'injury_date', //date
    ];

    protected $casts = [
        'injury_date' => 'date',
    ];

    /**
     * Relation vers le joueur.
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Méthode pour déterminer si une blessure est toujours active.
     */
    public function isActive()
    {
        $endDate = $this->injury_date->addDays($this->duration_in_days);
        return now()->lessThan($endDate);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'type', //enum('bonus', 'malus')
        'name', //string
        'description', //string
        'effects', //json
    ];

    protected $casts = [
        'effects' => 'array',
    ];

    /**
     * Scope pour obtenir des cartes de type bonus
     */
    public function scopeBonus($query)
    {
        return $query->where('type', 'bonus');
    }

    /**
     * Scope pour obtenir des cartes de type malus
     */
    public function scopeMalus($query)
    {
        return $query->where('type', 'malus');
    }

    // Si vous voulez obtenir l'effet exact d'une carte sur une statistique particulière
    public function getEffectOnStat($stat)
    {
        $effects = json_decode($this->effects, true);
        return $effects[$stat] ?? null;
    }
}

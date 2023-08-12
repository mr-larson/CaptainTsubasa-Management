<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Training extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'player_ids',
        'training_type_id',
        'stat_increase',
        'fatigue_generated',
        'training_mode',
    ];

    protected $casts = [
        'player_ids' => 'array',
        'stat_increase' => 'array',
    ];

    /**
     * Relation vers le type d'entraînement.
     */
    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class);
    }

    /**
     * Relation vers les joueurs concernés.
     */
    public function players()
    {
        return $this->belongsToMany(Player::class, 'trainings_players', 'training_id', 'player_id');
    }
}

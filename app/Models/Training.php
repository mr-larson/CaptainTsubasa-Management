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
        'training_type_id', //foreign key vers le type d'entraînement
        'training_date', //date
        'fatigue_generated', //json
        'training_mode', //enum
    ];

    protected $casts = [
        'training_date' => 'date',
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


<?php

namespace App\Models\GameSaves;

use Illuminate\Database\Eloquent\Model;

class GamePlayerChemistry extends Model
{
    public $timestamps = false;

    protected $casts = [
        'score' => 'integer',
        'shared_matches' => 'integer',
        'last_attempt_at_match_count' => 'integer',
        'confirmed' => 'boolean',
    ];
}

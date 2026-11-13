<?php

namespace App\Models\GameSaves;

use Illuminate\Database\Eloquent\Model;

/**
 * Alchimie entre deux joueurs d'une partie : score, matchs joués ensemble, dernière
 * tentative et confirmation (le duo est « appris »). Sans horodatage.
 */
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

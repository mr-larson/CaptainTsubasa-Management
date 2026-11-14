<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alchimie entre deux joueurs d'une partie : score, matchs joués ensemble, dernière
 * tentative et confirmation (le duo est « appris »). Sans horodatage.
 */
class GamePlayerChemistry extends Model
{
    use BelongsToGameSave;

    public $timestamps = false;

    protected $casts = [
        'score' => 'integer',
        'shared_matches' => 'integer',
        'last_attempt_at_match_count' => 'integer',
        'confirmed' => 'boolean',
    ];

    public function playerA(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class, 'player_a_id');
    }

    public function playerB(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class, 'player_b_id');
    }
}

<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Déclaration publique d'un coach sur un joueur (félicitations ou critique) : son
 * type, si elle était méritée, la réaction du joueur et les variations de relation
 * et de moral qui en résultent, à une semaine et une saison données.
 */
class GameDeclaration extends Model
{
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id',
        'game_player_id',
        'type',
        'deserved',
        'outcome',
        'affinity_delta',
        'morale_delta',
        'week',
        'season',
    ];

    protected $casts = [
        'deserved'       => 'boolean',
        'affinity_delta' => 'integer',
        'morale_delta'   => 'integer',
        'week'           => 'integer',
        'season'         => 'integer',
    ];

    /** Joueur concerné par la déclaration. */
    public function gamePlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class);
    }
}

<?php

namespace App\Models\GameSaves;

use App\Enums\PlayerRequestStatus;
use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande formulée par un joueur à son entraîneur : congé d'une semaine, temps de
 * jeu, ou souci physique à arbitrer (repos / kiné / jouer quand même). L'entraîneur
 * y répond pendant la semaine ; sans réponse, elle est ignorée à la clôture
 * (cf. PlayerRequestService).
 */
class GamePlayerRequest extends Model
{
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id',
        'game_team_id',
        'game_player_id',
        'type',
        'status',
        'choice',
        'description',
        'cost',
        'week',
        'season',
        'game_promise_id',
    ];

    protected $casts = [
        'status' => PlayerRequestStatus::class,
        'cost'   => 'integer',
        'week'   => 'integer',
        'season' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    /** Joueur qui formule la demande. */
    public function gamePlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class);
    }

    /** Équipe (donc entraîneur) à qui la demande est adressée. */
    public function gameTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class);
    }

    /** Promesse créée quand la demande a été acceptée (absente sinon). */
    public function gamePromise(): BelongsTo
    {
        return $this->belongsTo(GamePromise::class);
    }
}

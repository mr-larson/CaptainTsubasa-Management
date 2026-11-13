<?php

namespace App\Models\GameSaves;

use App\Enums\PromiseStatus;
use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Promesse faite à un joueur (temps de jeu, titularisation, repos, renouvellement) :
 * fenêtre en semaines, objectif, progression et statut (en cours / tenue / rompue /
 * expirée). Sa résolution ajuste le moral du joueur (cf. PromiseService).
 */
class GamePromise extends Model
{
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id',
        'game_player_id',
        'game_team_id',
        'type',
        'start_week',
        'due_week',
        'target_matches',
        'played_matches',
        'target_turns',
        'played_turns',
        'status',
        'season',
    ];

    protected $casts = [
        'status'         => PromiseStatus::class,
        'start_week'     => 'integer',
        'due_week'       => 'integer',
        'target_matches' => 'integer',
        'played_matches' => 'integer',
        'target_turns'   => 'integer',
        'played_turns'   => 'integer',
        'season'         => 'integer',
    ];

    /** Joueur à qui la promesse est faite. */
    public function gamePlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class);
    }

    /** Équipe qui a fait la promesse. */
    public function gameTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class);
    }
}

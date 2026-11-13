<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Blessure d'un joueur : gravité, durée, semaine de la blessure et semaine de
 * retour, match où elle est survenue. Rattachée à une saison (ScopedToSaveSeason).
 */
class GameInjury extends Model
{
    use BelongsToGameSave;
    use \App\Models\GameSaves\Concerns\ScopedToSaveSeason;

    protected $fillable = [
        'game_save_id',
        'season',
        'game_player_id',
        'game_match_id',
        'severity',
        'weeks_out',
        'week_injured',
        'week_return',
        'description',
    ];

    protected $casts = [
        'severity' => \App\Enums\InjurySeverity::class,
    ];

    /** Joueur blessé. */
    public function gamePlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class);
    }

    /** Match pendant lequel la blessure est survenue (peut être absent). */
    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class);
    }

    /**
     * Le joueur est-il encore blessé à la semaine donnée ? Vrai tant que la semaine de
     * retour n'est pas atteinte.
     */
    public function isActive(int $currentWeek): bool
    {
        return $currentWeek < $this->week_return;
    }
}

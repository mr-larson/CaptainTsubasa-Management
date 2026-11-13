<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sanction disciplinaire : carton jaune, double jaune ou rouge, avec la durée de
 * suspension, la semaine du match et celle du retour. Rattachée à une saison
 * (ScopedToSaveSeason).
 */
class GameSanction extends Model
{
    use BelongsToGameSave;
    use \App\Models\GameSaves\Concerns\ScopedToSaveSeason;

    protected $fillable = [
        'game_save_id',
        'season',
        'game_player_id',
        'game_match_id',
        'type',
        'weeks_suspended',
        'week_match',
        'week_return',
        'yellow_card_count',
    ];

    protected $casts = [
        'weeks_suspended'   => 'integer',
        'week_match'        => 'integer',
        'week_return'       => 'integer',
        'yellow_card_count' => 'integer',
    ];

    /**
     * Filtre les cartons jaunes du cycle en cours : tous ceux de la saison, ou seulement
     * ceux d'après la pause de mi-saison quand elle a eu lieu (les jaunes sont remis à
     * zéro à la pause).
     */
    public function scopeCurrentYellowCardCycle($query, GameSave $gameSave)
    {
        $break = $gameSave->gameState()->midseasonBreak();

        return $query->where('game_save_id', $gameSave->id)
            ->whereIn('type', ['yellow', 'double_yellow'])
            ->when($break['completed'] ?? false, fn ($query) => $query->where('week_match', '>=', $break['return_week']));
    }

    /** Joueur sanctionné. */
    public function gamePlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class);
    }

    /** Match où la sanction a été prononcée. */
    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class);
    }

    /**
     * Le joueur est-il encore sanctionné à la semaine donnée ? Vrai tant que la semaine
     * de retour n'est pas atteinte.
     */
    public function isActive(int $currentWeek): bool
    {
        return $currentWeek < $this->week_return;
    }

    /**
     * La sanction suspend-elle le joueur ? Vrai pour un rouge ou un double jaune
     * (un simple jaune ne suspend pas).
     */
    public function isSuspension(): bool
    {
        return in_array($this->type, ['red', 'double_yellow']);
    }
}

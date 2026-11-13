<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Formation à un poste ou à une technique : le joueur est indisponible tant que
 * week_return n'est pas atteinte (cf. PositionTrainingService).
 */
class GamePositionTraining extends Model
{
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id',
        'game_player_id',
        'game_team_id',
        'from_position',
        'target_position',
        'special_move',
        'cost',
        'week_start',
        'week_return',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'special_move' => 'array',
        'cost' => 'integer',
    ];

    /** Joueur en formation. */
    public function gamePlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class);
    }

    /**
     * Ids des joueurs en formation pour un match de la semaine donnée
     * (même convention que les blessures : absent tant que week_return > week).
     *
     * @return array<int>
     */
    public static function unavailablePlayerIds(int $gameSaveId, int $week): array
    {
        return static::where('game_save_id', $gameSaveId)
            ->whereNull('completed_at')
            ->where('week_start', '<=', $week)
            ->where('week_return', '>', $week)
            ->pluck('game_player_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}

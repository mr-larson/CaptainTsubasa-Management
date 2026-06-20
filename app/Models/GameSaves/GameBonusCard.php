<?php

namespace App\Models\GameSaves;

use App\Models\BonusCard;
use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameBonusCard extends Model
{
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id', 'bonus_card_id', 'game_team_id', 'tier', 'cost_paid',
        'status', 'target_player_id',
        'purchased_season', 'purchased_week',
        'used_season', 'used_week',
    ];

    public function bonusCard(): BelongsTo
    {
        return $this->belongsTo(BonusCard::class);
    }

    public function gameTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class);
    }

    public function targetPlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class, 'target_player_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}

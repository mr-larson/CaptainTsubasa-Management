<?php

namespace App\Models\GameSaves;

use App\Models\BonusCard;
use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Exemplaire d'une carte bonus/malus acheté par une équipe dans une partie : carte
 * d'origine, équipe propriétaire, prix payé, statut (disponible / utilisée), joueur
 * ciblé éventuel et semaines d'achat et d'usage.
 */
class GameBonusCard extends Model
{
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id', 'bonus_card_id', 'game_team_id', 'tier', 'cost_paid',
        'status', 'target_player_id',
        'purchased_season', 'purchased_week',
        'used_season', 'used_week',
    ];

    /** Carte du catalogue dont c'est un exemplaire. */
    public function bonusCard(): BelongsTo
    {
        return $this->belongsTo(BonusCard::class);
    }

    /** Équipe propriétaire de la carte. */
    public function gameTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class);
    }

    /** Joueur visé par la carte, pour les cartes qui ciblent un joueur. */
    public function targetPlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class, 'target_player_id');
    }

    /** La carte n'a-t-elle pas encore été utilisée ? */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}

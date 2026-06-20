<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameContract extends Model
{
    use HasFactory;
    use BelongsToGameSave;

    protected $fillable = [
        'game_save_id',
        'game_team_id',
        'game_player_id',
        'salary',
        'start_week',
        'end_week',
        'is_starter',
        'is_captain',
        'captain_rerolls_remaining',
        'captain_reroll_used_this_action',
    ];

    protected $casts = [
        'salary'                          => 'integer',
        'start_week'                      => 'integer',
        'end_week'                        => 'integer',
        'is_starter'                      => 'boolean',
        'is_captain'                      => 'boolean',
        'captain_rerolls_remaining'       => 'integer',
        'captain_reroll_used_this_action' => 'boolean',
    ];

    // ──────────────────────────────────────────────
    //   Relations
    // ──────────────────────────────────────────────

    public function gameTeam()
    {
        return $this->belongsTo(GameTeam::class, 'game_team_id');
    }

    public function gamePlayer()
    {
        return $this->belongsTo(GamePlayer::class, 'game_player_id');
    }

    // ──────────────────────────────────────────────
    //   Helpers gameplay
    // ──────────────────────────────────────────────

    public function isActive(int $currentWeek): bool
    {
        if ($this->end_week !== null && $currentWeek > $this->end_week) {
            return false;
        }
        return $currentWeek >= $this->start_week;
    }

    public function scopeActiveAt($query, int $currentWeek)
    {
        return $query->where('start_week', '<=', $currentWeek)
            ->where(function ($q) use ($currentWeek) {
                $q->whereNull('end_week')->orWhere('end_week', '>=', $currentWeek);
            });
    }

    // ──────────────────────────────────────────────
    //   Captain Reroll
    // ──────────────────────────────────────────────

    /**
     * Le capitaine peut-il relancer en ce moment ?
     * Conditions : est capitaine + relances restantes + pas encore utilisé cette action
     */
    public function canUseReroll(): bool
    {
        return $this->is_captain
            && $this->captain_rerolls_remaining > 0
            && ! $this->captain_reroll_used_this_action;
    }

    /**
     * Consomme une relance et pose le flag "utilisé cette action".
     * Retourne false si impossible.
     */
    public function useReroll(): bool
    {
        if (! $this->canUseReroll()) {
            return false;
        }

        $this->captain_rerolls_remaining--;
        $this->captain_reroll_used_this_action = true;
        $this->save();

        return true;
    }

    /**
     * Reset le flag entre deux actions (appelé au début de chaque nouvelle action).
     */
    public function resetRerollActionFlag(): void
    {
        if ($this->captain_reroll_used_this_action) {
            $this->captain_reroll_used_this_action = false;
            $this->save();
        }
    }

    /**
     * Reset complet pour un nouveau match.
     */
    public function resetForNewMatch(): void
    {
        $this->captain_rerolls_remaining       = 3;
        $this->captain_reroll_used_this_action = false;
        $this->save();
    }

    /**
     * Retourne le statut capitaine pour le frontend.
     */
    public function captainRerollStatus(): array
    {
        return [
            'contractId'       => $this->id,
            'isCaptain'        => $this->is_captain,
            'rerollsRemaining' => $this->captain_rerolls_remaining,
            'canReroll'        => $this->canUseReroll(),
            'usedThisAction'   => $this->captain_reroll_used_this_action,
        ];
    }
}

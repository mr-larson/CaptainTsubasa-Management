<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use App\Models\GameSaves\Concerns\ManagesCaptainRerolls;
use App\Models\GameSaves\Concerns\ScopedToSaveSeason;
use App\Observers\GameContractObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contrat d'un joueur avec une équipe dans une sauvegarde : salaire, semaines
 * de début et de fin, titulaire ou remplaçant, capitaine.
 *
 * - Un contrat appartient à une saison (ScopedToSaveSeason) : à l'intersaison les
 *   contrats expirent mais restent en base comme historique.
 * - Les relances du capitaine sont gérées par ManagesCaptainRerolls.
 * - La signature d'un contrat déclenche le comptage du défi « Recruter X
 *   joueurs » via GameContractObserver.
 */
#[ObservedBy([GameContractObserver::class])]
class GameContract extends Model
{
    use HasFactory;
    use BelongsToGameSave;
    use ScopedToSaveSeason;
    use ManagesCaptainRerolls;

    /** Colonnes affectables en masse. */
    protected $fillable = [
        'game_save_id',
        'season',
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

    /** Conversions : entiers pour les semaines et le salaire, booléens pour les drapeaux. */
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

    /** Équipe qui emploie le joueur. */
    public function gameTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class, 'game_team_id');
    }

    /** Joueur sous contrat. */
    public function gamePlayer(): BelongsTo
    {
        return $this->belongsTo(GamePlayer::class, 'game_player_id');
    }

    // ──────────────────────────────────────────────
    //   Validité du contrat
    // ──────────────────────────────────────────────

    /**
     * Le contrat est-il en vigueur à la semaine donnée ? Vrai entre sa semaine de
     * début et sa semaine de fin incluses (fin nulle = jusqu'à la fin de la saison).
     */
    public function isActive(int $currentWeek): bool
    {
        if ($this->end_week !== null && $currentWeek > $this->end_week) {
            return false;
        }
        return $currentWeek >= $this->start_week;
    }

    /**
     * Filtre SQL équivalent à isActive() : les contrats en vigueur à la semaine donnée.
     * Usage : GameContract::activeAt($week)->get().
     */
    public function scopeActiveAt($query, int $currentWeek)
    {
        return $query->where('start_week', '<=', $currentWeek)
            ->where(function ($q) use ($currentWeek) {
                $q->whereNull('end_week')->orWhere('end_week', '>=', $currentWeek);
            });
    }
}

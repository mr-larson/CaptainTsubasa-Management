<?php

namespace App\Models\GameSaves;

use App\Enums\MatchStatus;
use App\Models\GameSaves\Concerns\BelongsToGameSave;
use App\Models\GameSaves\Concerns\HasMatchDetails;
use App\Models\GameSaves\Concerns\ScopedToSaveSeason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un match du calendrier d'une sauvegarde : deux équipes, une semaine, un statut
 * (programmé / joué / annulé), un score et, une fois joué, son détail.
 *
 * - Chaque match appartient à une saison (ScopedToSaveSeason) : les requêtes ne
 *   voient que la saison courante, l'historique reste lisible via allSeasons().
 * - Le détail du match (bilans joueurs, déroulé) est rangé hors de la table par
 *   HasMatchDetails : voir ce trait pour playerStatsMap(), eventList(), fullStats().
 */
class GameMatch extends Model
{
    use HasFactory;
    use BelongsToGameSave;
    use ScopedToSaveSeason;
    use HasMatchDetails;

    /** Colonnes affectables en masse. `round` sert au mode Coupe du Monde (poule, demi, finale). */
    protected $fillable = [
        'game_save_id',
        'season',
        'week',
        'home_team_id',
        'away_team_id',
        'status',
        'round',
        'home_score',
        'away_score',
        'match_stats'
    ];

    /** Conversions : résumé en tableau, statut en enum, drapeau calculé par la page Play. */
    protected $casts = [
        'match_stats' => 'array',
        'status'      => MatchStatus::class,
        // Colonne calculée (select de la page Play) : détail disponible ou non.
        'has_stats'   => 'boolean',
    ];

    /** Équipe qui reçoit. */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class, 'home_team_id');
    }

    /** Équipe qui se déplace. */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class, 'away_team_id');
    }
}

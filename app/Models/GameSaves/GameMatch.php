<?php

namespace App\Models\GameSaves;

use App\Models\GameSaves\Concerns\BelongsToGameSave;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Détail d'un match joué, réparti sur trois emplacements :
 *  - game_matches.match_stats        : résumé léger (teams, starters, playtime,
 *                                      lineups, progression, shootout…) ;
 *  - game_match_player_stats         : bilan par joueur  → playerStatsMap() ;
 *  - game_match_events (1-1)         : déroulé complet   → eventList().
 *
 * L'écriture reste unique : affecter à match_stats un tableau contenant
 * `players` et/ou `events` les range dans leurs tables à l'enregistrement
 * (une clé absente laisse la table correspondante intacte). fullStats()
 * recompose le tableau d'origine pour la feuille de match.
 */
class GameMatch extends Model
{
    use HasFactory;
    use BelongsToGameSave;
    use \App\Models\GameSaves\Concerns\ScopedToSaveSeason;

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

    protected $casts = [
        'match_stats' => 'array',
        // Colonne calculée (select de la page Play) : détail disponible ou non.
        'has_stats'   => 'boolean',
    ];

    /** Bilans joueurs / déroulé affectés via match_stats, en attente d'enregistrement. */
    private ?array $pendingPlayerStats = null;
    private ?array $pendingEvents = null;

    protected static function booted(): void
    {
        static::saved(fn (GameMatch $match) => $match->flushPendingDetails());
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(GameTeam::class, 'away_team_id');
    }

    public function playerStats(): HasMany
    {
        return $this->hasMany(GameMatchPlayerStat::class);
    }

    public function eventLog(): HasOne
    {
        return $this->hasOne(GameMatchEventLog::class);
    }

    /** Sépare `players` et `events` du résumé stocké dans la colonne. */
    public function setMatchStatsAttribute(mixed $value): void
    {
        if ($value === null) {
            $this->pendingPlayerStats = [];
            $this->pendingEvents      = [];
            $this->attributes['match_stats'] = null;

            return;
        }

        $value = (array) $value;
        if (array_key_exists('players', $value)) {
            $this->pendingPlayerStats = is_array($value['players']) ? $value['players'] : [];
            unset($value['players']);
        }
        if (array_key_exists('events', $value)) {
            $this->pendingEvents = is_array($value['events']) ? array_values($value['events']) : [];
            unset($value['events']);
        }

        $this->attributes['match_stats'] = json_encode($value);
    }

    /**
     * Bilan par joueur, indexé par game_player_id, dans la forme historique
     * de match_stats.players (offense / defense / duelsWon / duelsLost).
     * Précharger `playerStats` quand plusieurs matchs sont parcourus.
     */
    public function playerStatsMap(): array
    {
        if ($this->pendingPlayerStats !== null) {
            return $this->pendingPlayerStats;
        }
        if (! $this->exists) {
            return [];
        }

        $map = [];
        foreach ($this->playerStats as $row) {
            $map[(int) $row->game_player_id] = $row->toStats();
        }

        return $map;
    }

    /** Déroulé du match (liste d'événements). */
    public function eventList(): array
    {
        if ($this->pendingEvents !== null) {
            return $this->pendingEvents;
        }

        return $this->exists ? ($this->eventLog?->events ?? []) : [];
    }

    /** match_stats complet (résumé + players + events), pour la feuille de match. */
    public function fullStats(): ?array
    {
        $stats = $this->match_stats;
        if ($stats === null) {
            return null;
        }

        $players = $this->playerStatsMap();
        $events  = $this->eventList();
        if ($players) {
            $stats['players'] = $players;
        }
        if ($events) {
            $stats['events'] = $events;
        }

        return $stats;
    }

    private function flushPendingDetails(): void
    {
        if ($this->pendingPlayerStats !== null) {
            $rows = [];
            foreach ($this->pendingPlayerStats as $playerId => $stats) {
                if ((int) $playerId <= 0) {
                    continue;
                }
                $rows[] = [
                    'game_save_id'   => $this->game_save_id,
                    'game_match_id'  => $this->id,
                    'game_player_id' => (int) $playerId,
                ] + GameMatchPlayerStat::columnsFromStats($stats);
            }

            $this->playerStats()->delete();
            foreach (array_chunk($rows, 100) as $chunk) {
                GameMatchPlayerStat::insert($chunk);
            }
            $this->pendingPlayerStats = null;
            $this->unsetRelation('playerStats');
        }

        if ($this->pendingEvents !== null) {
            if ($this->pendingEvents) {
                GameMatchEventLog::updateOrCreate(
                    ['game_match_id' => $this->id],
                    ['events' => $this->pendingEvents],
                );
            } else {
                $this->eventLog()->delete();
            }
            $this->pendingEvents = null;
            $this->unsetRelation('eventLog');
        }
    }
}

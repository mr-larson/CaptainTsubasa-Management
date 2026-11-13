<?php

namespace App\Models\GameSaves\Concerns;

use App\Models\GameSaves\GameMatchEventLog;
use App\Models\GameSaves\GameMatchPlayerStat;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Détail d'un match joué, réparti sur trois emplacements pour garder la table
 * `game_matches` légère :
 *  - game_matches.match_stats  : résumé (teams, starters, playtime, lineups,
 *                                progression, shootout…) ;
 *  - game_match_player_stats   : une ligne par joueur et par match → playerStatsMap() ;
 *  - game_match_events (1-1)   : déroulé complet du match         → eventList().
 *
 * L'écriture reste unique : affecter à `match_stats` un tableau contenant
 * `players` et/ou `events` les range dans leurs tables à l'enregistrement du
 * match (une clé absente laisse la table correspondante intacte). `fullStats()`
 * recompose le tableau d'origine pour la feuille de match.
 *
 * Le modèle qui utilise ce trait doit avoir une colonne `match_stats` castée en
 * tableau et un `game_save_id`.
 */
trait HasMatchDetails
{
    /** Bilans joueurs affectés via match_stats, en attente d'enregistrement (null = rien à écrire). */
    private ?array $pendingPlayerStats = null;

    /** Déroulé affecté via match_stats, en attente d'enregistrement (null = rien à écrire). */
    private ?array $pendingEvents = null;

    /**
     * Branche l'enregistrement différé : une fois le match sauvegardé (il a alors
     * un id), les bilans joueurs et le déroulé en attente sont écrits dans leurs tables.
     */
    public static function bootHasMatchDetails(): void
    {
        static::saved(fn ($match) => $match->flushPendingDetails());
    }

    /** Bilans joueurs de ce match (une ligne par joueur). */
    public function playerStats(): HasMany
    {
        return $this->hasMany(GameMatchPlayerStat::class);
    }

    /** Déroulé de ce match (une seule ligne, chargée uniquement sur la feuille de match). */
    public function eventLog(): HasOne
    {
        return $this->hasOne(GameMatchEventLog::class);
    }

    /**
     * Mutateur de `match_stats` : met de côté `players` et `events` (écrits dans
     * leurs tables à la sauvegarde) et ne garde dans la colonne que le résumé.
     * Affecter null vide aussi les bilans et le déroulé.
     */
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
     * Bilan par joueur, indexé par game_player_id, dans la forme historique de
     * match_stats.players (offense / defense / duelsWon / duelsLost). Renvoie les
     * valeurs en attente si le match n'est pas encore sauvegardé.
     * Précharger `playerStats` quand plusieurs matchs sont parcourus.
     *
     * @return array<int, array<string, mixed>>
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

    /**
     * Déroulé du match : liste d'événements (passes, tirs, duels…), ou liste vide
     * si le match n'en a pas (match simulé sans déroulé, ou pas encore joué).
     *
     * @return array<int, array<string, mixed>>
     */
    public function eventList(): array
    {
        if ($this->pendingEvents !== null) {
            return $this->pendingEvents;
        }

        return $this->exists ? ($this->eventLog?->events ?? []) : [];
    }

    /**
     * match_stats complet (résumé + players + events), tel que l'attend la feuille
     * de match. Null si le match n'a aucun résumé enregistré.
     */
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

    /**
     * Écrit dans leurs tables les bilans joueurs et le déroulé mis en attente par
     * setMatchStatsAttribute(), puis vide l'attente. Les bilans remplacent les lignes
     * existantes du match ; un déroulé vide supprime la ligne de déroulé.
     */
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

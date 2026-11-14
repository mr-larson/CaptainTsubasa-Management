<?php

namespace App\Services\Players;

use App\Enums\MoraleSource;
use App\Enums\PromiseStatus;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Promesses du coach aux joueurs.
 * Tenue → affinité et moral montent ; rompue → les deux chutent fortement.
 *
 * Types :
 *  - playing_time : « je te ferai jouer » → au moins `target_turns` tours au
 *    PROCHAIN match où le joueur est disponible (un vrai temps de jeu, même
 *    en sortant du banc).
 *  - starter      : « tu seras titulaire » → figurer dans le 11 de départ sur
 *    `target_matches` matchs de la fenêtre.
 *  - renewal      : tenue si un nouveau contrat est signé avant l'échéance.
 */
class PromiseService
{
    // Repli « titulaire = match complet » pour les vieux matchs sans playtime
    // (joués quand la durée par défaut était de 45 tours).
    const FULL_MATCH_TURNS = 45;

    /**
     * Temps de jeu promis : un tiers de la durée de match réglée pour la save
     * (10 tours sur 30 par défaut, 15 sur 45).
     */
    /** Temps de jeu promis : un tiers de la durée de match réglée pour la save (10 tours sur 30 par défaut, 15 sur 45). */
    public static function playingTimeTarget(GameSave $gameSave): int
    {
        return max(1, (int) ceil((int) $gameSave->getConfig('match_max_turns') / 3));
    }

    // Paramètres par type : window (semaines), target (matchs), turns (tours),
    // kept / broken : delta appliqué à la relation coach ET au moral.
    const TYPES = [
        'playing_time' => ['window' => 5, 'target' => 0, 'turns' => 15, 'kept' => 5,  'broken' => -10],
        'starter'      => ['window' => 5, 'target' => 4,                'kept' => 10, 'broken' => -15],
        'renewal'      => ['window' => 4, 'target' => 0,                'kept' => 15, 'broken' => -20],
        'rest'         => ['window' => 1, 'target' => 0,                'kept' => 5,  'broken' => -10],
    ];

    const AFFINITY_MIN = -100;
    const AFFINITY_MAX = 100;

    /**
     * Crée une promesse. Retourne le modèle, ou un message d'erreur (string).
     */
    public function create(GameSave $gameSave, GamePlayer $player, int $teamId, string $type = 'playing_time'): GamePromise|string
    {
        $config = self::TYPES[$type] ?? null;
        if (!$config) {
            return 'Type de promesse inconnu.';
        }

        $alreadyPending = GamePromise::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->where('status', PromiseStatus::Pending)
            ->exists();

        if ($alreadyPending) {
            return 'Une promesse est déjà en cours pour ce joueur.';
        }

        $week = (int) ($gameSave->week ?? 1);

        return GamePromise::create([
            'game_save_id'   => $gameSave->id,
            'game_player_id' => $player->id,
            'game_team_id'   => $teamId,
            'type'           => $type,
            'start_week'     => $week,
            'due_week'       => $week + $config['window'] - 1,
            'target_matches' => $config['target'] ?? 0,
            'target_turns'   => isset($config['turns']) ? self::playingTimeTarget($gameSave) : null,
            'season'         => (int) ($gameSave->season ?? 1),
        ]);
    }

    /** Prolongation signée : si le joueur a une promesse de prolongation en cours (saison et semaine valides), la marque tenue tout de suite et journalise le moral. Retourne true si une promesse a été honorée. */
    public function fulfillRenewal(GameSave $gameSave, GamePlayer $player, int $teamId): bool
    {
        $week = (int) ($gameSave->week ?? 1);
        $season = (int) ($gameSave->season ?? 1);
        $promise = GamePromise::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->where('game_team_id', $teamId)
            ->where('season', $season)
            ->where('type', 'renewal')
            ->where('status', PromiseStatus::Pending)
            ->where('start_week', '<=', $week)
            ->where('due_week', '>=', $week)
            ->first();

        if (!$promise) return false;

        $now = now();
        $promise->renewal_signed_at = $now;
        GamePlayerMoraleLog::insert([
            $this->resolve($promise, $player, [true, 'Promesse de prolongation tenue', null, null], $week, $season, $now),
        ]);

        return true;
    }

    /**
     * Évalue les promesses en cours (appelé après chaque semaine jouée).
     * Chaque évaluateur renvoie [kept, label, playedMatches, playedTurns] si la
     * promesse est résolue, ou null si elle doit rester en attente.
     */
    public function evaluateForWeek(GameSave $gameSave, int $week): void
    {
        $promises = GamePromise::where('game_save_id', $gameSave->id)
            ->where('status', PromiseStatus::Pending)
            ->with('gamePlayer')
            ->get();

        if ($promises->isEmpty()) return;

        $season = (int) ($gameSave->season ?? 1);
        $now    = now();
        $logs   = [];
        $starterProgress = $this->starterProgress($gameSave, $promises, $week);

        foreach ($promises as $promise) {
            $player = $promise->gamePlayer;
            if (!$player) {
                $promise->update(['status' => PromiseStatus::Broken]);
                continue;
            }

            $resolution = match ($promise->type) {
                'renewal'      => $this->evaluateRenewal($promise, $week),
                'playing_time' => $this->evaluatePlayingTime($promise, $week),
                'rest'         => $this->evaluateRest($gameSave, $promise, $week),
                default        => $this->evaluateStarter($promise, $week, $starterProgress[$promise->id] ?? 0),
            };

            // null = pas encore résolue (ex. prochain match pas encore joué).
            if ($resolution === null) continue;

            $logs[] = $this->resolve($promise, $player, $resolution, $week, $season, $now);
        }

        if (!empty($logs)) {
            GamePlayerMoraleLog::insert($logs);
        }
    }

    /** Clôture une promesse (tenue ou rompue), applique le delta à la relation coach et au moral du joueur (bornés) et renvoie la ligne de journal de moral à insérer. */
    private function resolve(GamePromise $promise, GamePlayer $player, array $resolution, int $week, int $season, Carbon $now): array
    {
        [$kept, $label, $playedMatches, $playedTurns] = $resolution;

        $promise->update([
            'status'         => $kept ? PromiseStatus::Kept : PromiseStatus::Broken,
            'played_matches' => $playedMatches,
            'played_turns'   => $playedTurns,
        ]);

        $delta = $this->delta($promise->type, $kept);

        $player->coach_affinity = max(self::AFFINITY_MIN, min(self::AFFINITY_MAX,
            (int) $player->coach_affinity + $delta
        ));
        $player->morale = max(0, min(100, (int) $player->morale + $delta));
        $player->save();

        return [
            'game_save_id'   => $promise->game_save_id,
            'game_player_id' => $player->id,
            'source'         => MoraleSource::Promise->value,
            'value'          => $delta,
            'label'          => $label,
            'week'           => $week,
            'season'         => $season,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];
    }

    /**
     * Delta relation/moral d'une promesse résolue selon son type.
     */
    private function delta(string $type, bool $kept): int
    {
        $config = self::TYPES[$type] ?? self::TYPES['starter'];

        return $kept ? $config['kept'] : $config['broken'];
    }

    /** Repos : tenue si le joueur n'a joué aucun tour ni été entraîné ou formé pendant la semaine de la promesse. Résolue à l'échéance, null avant. */
    private function evaluateRest(GameSave $gameSave, GamePromise $promise, int $week): ?array
    {
        if ($week < $promise->due_week) return null;

        $restWeek = $promise->start_week;
        $playerId = (int) $promise->game_player_id;
        $turns = GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', $restWeek)
            ->where('status', 'played')
            ->where(fn ($q) => $q->where('home_team_id', $promise->game_team_id)
                ->orWhere('away_team_id', $promise->game_team_id))
            ->get()
            ->sum(fn (GameMatch $match) => $this->turnsPlayed($match, $playerId));

        $sessions = $gameSave->gameState()->weekTrainingEntries((int) $promise->season, (int) $restWeek);
        $trained = collect($sessions)->contains(fn ($entry) => (int) $entry['player_id'] === $playerId)
            || collect($gameSave->gameState()->aiTrainingHistory())->contains(fn ($entry) =>
                (int) $entry['player_id'] === $playerId && (int) $entry['season'] === $promise->season
                && (int) $entry['week'] === $restWeek
            )
            || GamePositionTraining::where('game_save_id', $gameSave->id)
                ->where('game_player_id', $playerId)
                ->where('week_start', '<=', $restWeek)
                ->where('week_return', '>', $restWeek)
                ->exists();

        $kept = $turns === 0 && !$trained;
        return [$kept, $kept ? 'Promesse de repos tenue' : 'Promesse de repos non tenue', null, $turns];
    }

    /**
     * Prolongation : tenue si un nouveau contrat a été signé après la promesse.
     * Rattrape les prolongations déjà enregistrées sur les anciennes sauvegardes.
     */
    private function evaluateRenewal(GamePromise $promise, int $week): ?array
    {
        if ($week < $promise->due_week && $promise->renewal_signed_at === null) return null;

        $kept = $promise->renewal_signed_at !== null || \App\Models\GameSaves\GameContract::where('game_save_id', $promise->game_save_id)
            ->where('game_player_id', $promise->game_player_id)
            ->where('game_team_id', $promise->game_team_id)
            ->where('created_at', '>', $promise->created_at)
            ->exists();

        return [
            $kept,
            $kept ? 'Promesse de prolongation tenue' : 'Promesse de prolongation non tenue',
            null,
            null,
        ];
    }

    /**
     * Titularisation : figurer dans le 11 de départ sur `target_matches` matchs
     * de la fenêtre. Résolue à l'échéance.
     */
    private function evaluateStarter(GamePromise $promise, int $week, int $started): ?array
    {
        if ($week < $promise->due_week) {
            if ($promise->played_matches !== $started) {
                $promise->update(['played_matches' => $started]);
            }
            return null;
        }

        $target  = $this->effectiveTarget($promise);
        $kept    = $started >= $target;

        return [
            $kept,
            $kept
                ? "Promesse tenue ({$started}/{$target} titularisations)"
                : "Promesse non tenue ({$started}/{$target} titularisations)",
            $started,
            null,
        ];
    }

    /**
     * Temps de jeu : au moins `target_turns` tours au PROCHAIN match où le joueur
     * est disponible. Un match raté pour blessure/suspension ne compte pas (on
     * passe au suivant). Au-delà de l'échéance sans aucune opportunité, on gèle
     * (bénéfice du doute, pas de pénalité).
     */
    private function evaluatePlayingTime(GamePromise $promise, int $week): ?array
    {
        $matches = GameMatch::where('game_save_id', $promise->game_save_id)
            ->whereBetween('week', [$promise->start_week, $week])
            ->where('status', 'played')
            ->where(fn($q) => $q
                ->where('home_team_id', $promise->game_team_id)
                ->orWhere('away_team_id', $promise->game_team_id))
            ->orderBy('week')->orderBy('id')
            ->get();

        $target = (int) ($promise->target_turns ?? self::TYPES['playing_time']['turns']);

        foreach ($matches as $match) {
            // Indisponible cette semaine-là : on ne lui reproche pas ce match.
            if ($this->wasUnavailable($promise, (int) $match->week)) continue;

            // Premier match disponible = « le prochain match » → on tranche.
            $turns = $this->turnsPlayed($match, (int) $promise->game_player_id);
            $kept  = $turns >= $target;

            return [
                $kept,
                $kept
                    ? "Promesse tenue ({$turns}/{$target} tours joués)"
                    : "Promesse non tenue ({$turns}/{$target} tours joués)",
                null,
                $turns,
            ];
        }

        // Aucun match disponible joué dans la fenêtre.
        if ($week >= $promise->due_week) {
            return [true, 'Promesse gelée (aucun match disponible)', null, null];
        }

        return null; // on réessaiera la semaine prochaine
    }

    /**
     * Titularisations par promesse en cours, d'après les matchs déjà joués.
     * Un seul chargement des feuilles de match pour toutes les promesses.
     */
    public function starterProgress(GameSave $gameSave, Collection $promises, int $week): array
    {
        $promises = $promises->where('type', 'starter')->where('status', PromiseStatus::Pending)
            ->filter(fn (GamePromise $promise) => $promise->start_week <= $week);
        if ($promises->isEmpty()) return [];

        $teamIds = $promises->pluck('game_team_id')->unique()->values();
        $matches = GameMatch::where('game_save_id', $gameSave->id)
            ->whereBetween('week', [$promises->min('start_week'), min($week, $promises->max('due_week'))])
            ->where('status', 'played')
            ->where(fn ($q) => $q
                ->whereIn('home_team_id', $teamIds)
                ->orWhereIn('away_team_id', $teamIds))
            ->get();

        return $promises->mapWithKeys(fn (GamePromise $promise) => [
            $promise->id => $matches->filter(fn (GameMatch $match) =>
                $match->week >= $promise->start_week
                && $match->week <= $promise->due_week
                && ((int) $match->home_team_id === (int) $promise->game_team_id
                    || (int) $match->away_team_id === (int) $promise->game_team_id)
                && $this->startedMatch($match, (int) $promise->game_player_id)
            )->count(),
        ])->all();
    }

    /**
     * Le joueur figurait-il dans le 11 de départ de ce match ?
     * Source : match_stats.starters ; repli (vieux matchs) : participation.
     */
    private function startedMatch(GameMatch $match, int $playerId): bool
    {
        $pid      = (string) $playerId;
        $starters = $match->match_stats['starters'] ?? null;

        if (is_array($starters)) {
            $all = array_map('strval', array_merge($starters['home'] ?? [], $starters['away'] ?? []));
            return in_array($pid, $all, true);
        }

        return isset(MoraleService::resolvePlayedPlayerIds($match)[$pid]);
    }

    /**
     * Tours joués par le joueur sur ce match.
     * Source : match_stats.playtime ; replis : titulaire = match complet, sinon 0.
     */
    private function turnsPlayed(GameMatch $match, int $playerId): int
    {
        $pid      = (string) $playerId;
        $playtime = $match->match_stats['playtime'] ?? null;

        if (is_array($playtime) && array_key_exists($pid, $playtime)) {
            return (int) $playtime[$pid];
        }

        if ($this->startedMatch($match, $playerId)) {
            return self::FULL_MATCH_TURNS;
        }

        return 0;
    }

    /**
     * Cible réduite des semaines d'indisponibilité (blessure/suspension) :
     * on ne reproche pas au coach les matchs que le joueur ne pouvait pas jouer.
     */
    private function effectiveTarget(GamePromise $promise): int
    {
        $unavailableWeeks = 0;

        for ($w = $promise->start_week; $w <= $promise->due_week; $w++) {
            if ($this->wasUnavailable($promise, $w)) $unavailableWeeks++;
        }

        return max(1, (int) $promise->target_matches - $unavailableWeeks);
    }

    /**
     * Le joueur était-il blessé ou suspendu lors de la semaine donnée ?
     */
    private function wasUnavailable(GamePromise $promise, int $week): bool
    {
        $injured = GameInjury::where('game_save_id', $promise->game_save_id)
            ->where('game_player_id', $promise->game_player_id)
            ->where('week_injured', '<=', $week)
            ->where('week_return', '>', $week)
            ->exists();

        if ($injured) return true;

        return GameSanction::where('game_save_id', $promise->game_save_id)
            ->where('game_player_id', $promise->game_player_id)
            ->where('weeks_suspended', '>', 0)
            ->where('week_match', '<=', $week)
            ->where('week_return', '>', $week)
            ->exists();
    }
}

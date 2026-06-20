<?php

namespace App\Services;

use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;

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
    // Nombre de tours d'un match complet (miroir MatchSimulator::MAX_TURNS /
    // moteur client GAME_RULES.MAX_TURNS).
    const FULL_MATCH_TURNS = 45;

    // Paramètres par type : window (semaines), target (matchs), turns (tours).
    const TYPES = [
        'playing_time' => ['window' => 5, 'target' => 0, 'turns' => 15],
        'starter'      => ['window' => 5, 'target' => 4],
        'renewal'      => ['window' => 4, 'target' => 0],
    ];

    const AFFINITY_KEPT   = 15;
    const AFFINITY_BROKEN = -25;
    const MORALE_KEPT     = 5;
    const MORALE_BROKEN   = -10;

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
            ->where('status', 'pending')
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
            'target_turns'   => $config['turns'] ?? null,
            'season'         => (int) ($gameSave->season ?? 1),
        ]);
    }

    /**
     * Évalue les promesses en cours (appelé après chaque semaine jouée).
     * Chaque évaluateur renvoie [kept, label, playedMatches, playedTurns] si la
     * promesse est résolue, ou null si elle doit rester en attente.
     */
    public function evaluateForWeek(GameSave $gameSave, int $week): void
    {
        $promises = GamePromise::where('game_save_id', $gameSave->id)
            ->where('status', 'pending')
            ->with('gamePlayer')
            ->get();

        if ($promises->isEmpty()) return;

        $season = (int) ($gameSave->season ?? 1);
        $now    = now();
        $logs   = [];

        foreach ($promises as $promise) {
            $player = $promise->gamePlayer;
            if (!$player) {
                $promise->update(['status' => 'broken']);
                continue;
            }

            $resolution = match ($promise->type) {
                'renewal'      => $this->evaluateRenewal($promise, $week),
                'playing_time' => $this->evaluatePlayingTime($promise, $week),
                default        => $this->evaluateStarter($promise, $week),
            };

            // null = pas encore résolue (ex. prochain match pas encore joué).
            if ($resolution === null) continue;

            [$kept, $label, $playedMatches, $playedTurns] = $resolution;

            $promise->update([
                'status'         => $kept ? 'kept' : 'broken',
                'played_matches' => $playedMatches,
                'played_turns'   => $playedTurns,
            ]);

            $player->coach_affinity = max(self::AFFINITY_MIN, min(self::AFFINITY_MAX,
                (int) $player->coach_affinity + ($kept ? self::AFFINITY_KEPT : self::AFFINITY_BROKEN)
            ));
            $player->morale = max(0, min(100,
                (int) $player->morale + ($kept ? self::MORALE_KEPT : self::MORALE_BROKEN)
            ));
            $player->save();

            $logs[] = [
                'game_save_id'   => $gameSave->id,
                'game_player_id' => $player->id,
                'source'         => 'promise',
                'value'          => $kept ? self::MORALE_KEPT : self::MORALE_BROKEN,
                'label'          => $label,
                'week'           => $week,
                'season'         => $season,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        if (!empty($logs)) {
            GamePlayerMoraleLog::insert($logs);
        }
    }

    /**
     * Prolongation : tenue si un nouveau contrat a été signé après la promesse.
     * Résolue uniquement à l'échéance.
     */
    private function evaluateRenewal(GamePromise $promise, int $week): ?array
    {
        if ($week < $promise->due_week) return null;

        $kept = \App\Models\GameSaves\GameContract::where('game_save_id', $promise->game_save_id)
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
    private function evaluateStarter(GamePromise $promise, int $week): ?array
    {
        if ($week < $promise->due_week) return null;

        $started = $this->countStartedMatches($promise);
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
     * Nombre de matchs de la fenêtre où le joueur figurait dans le 11 de départ.
     */
    private function countStartedMatches(GamePromise $promise): int
    {
        $matches = GameMatch::where('game_save_id', $promise->game_save_id)
            ->whereBetween('week', [$promise->start_week, $promise->due_week])
            ->where('status', 'played')
            ->where(fn($q) => $q
                ->where('home_team_id', $promise->game_team_id)
                ->orWhere('away_team_id', $promise->game_team_id))
            ->get();

        $count = 0;
        foreach ($matches as $match) {
            if ($this->startedMatch($match, (int) $promise->game_player_id)) $count++;
        }

        return $count;
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

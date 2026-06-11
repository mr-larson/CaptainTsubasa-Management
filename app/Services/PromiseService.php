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
 * Promesses du coach aux joueurs — phase 3.
 * Type unique pour l'instant : playing_time ("je te ferai jouer").
 * Tenue → affinité et moral montent ; rompue → les deux chutent fortement.
 */
class PromiseService
{
    // Paramètres par type : fenêtre en semaines et matchs à jouer.
    // renewal : pas de cible de matchs — tenue si un nouveau contrat est signé avant l'échéance.
    const TYPES = [
        'playing_time' => ['window' => 5, 'target' => 3],
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
            'target_matches' => $config['target'],
            'season'         => (int) ($gameSave->season ?? 1),
        ]);
    }

    /**
     * Évalue les promesses arrivées à échéance (appelé après chaque semaine jouée).
     */
    public function evaluateForWeek(GameSave $gameSave, int $week): void
    {
        $promises = GamePromise::where('game_save_id', $gameSave->id)
            ->where('status', 'pending')
            ->where('due_week', '<=', $week)
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

            if ($promise->type === 'renewal') {
                // Tenue si un nouveau contrat a été signé après la promesse
                $kept   = \App\Models\GameSaves\GameContract::where('game_save_id', $promise->game_save_id)
                    ->where('game_player_id', $promise->game_player_id)
                    ->where('game_team_id', $promise->game_team_id)
                    ->where('created_at', '>', $promise->created_at)
                    ->exists();
                $played = null;
                $label  = $kept ? 'Promesse de prolongation tenue' : 'Promesse de prolongation non tenue';
            } else {
                $played = $this->countPlayedMatches($promise);
                $target = $this->effectiveTarget($promise);
                $kept   = $played >= $target;
                $label  = $kept
                    ? "Promesse tenue ({$played}/{$target} matchs joués)"
                    : "Promesse non tenue ({$played}/{$target} matchs joués)";
            }

            $promise->update([
                'status'         => $kept ? 'kept' : 'broken',
                'played_matches' => $played,
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
     * Matchs de l'équipe joués dans la fenêtre où le joueur a participé.
     */
    private function countPlayedMatches(GamePromise $promise): int
    {
        $matches = GameMatch::where('game_save_id', $promise->game_save_id)
            ->whereBetween('week', [$promise->start_week, $promise->due_week])
            ->where('status', 'played')
            ->where(fn($q) => $q
                ->where('home_team_id', $promise->game_team_id)
                ->orWhere('away_team_id', $promise->game_team_id))
            ->get();

        $playerId = (string) $promise->game_player_id;
        $count    = 0;

        foreach ($matches as $match) {
            if (isset(MoraleService::resolvePlayedPlayerIds($match)[$playerId])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Cible réduite des semaines d'indisponibilité (blessure/suspension) :
     * on ne reproche pas au coach les matchs que le joueur ne pouvait pas jouer.
     */
    private function effectiveTarget(GamePromise $promise): int
    {
        $unavailableWeeks = 0;

        for ($w = $promise->start_week; $w <= $promise->due_week; $w++) {
            $injured = GameInjury::where('game_save_id', $promise->game_save_id)
                ->where('game_player_id', $promise->game_player_id)
                ->where('week_injured', '<=', $w)
                ->where('week_return', '>', $w)
                ->exists();

            $suspended = !$injured && GameSanction::where('game_save_id', $promise->game_save_id)
                ->where('game_player_id', $promise->game_player_id)
                ->where('weeks_suspended', '>', 0)
                ->where('week_match', '<=', $w)
                ->where('week_return', '>', $w)
                ->exists();

            if ($injured || $suspended) $unavailableWeeks++;
        }

        return max(1, (int) $promise->target_matches - $unavailableWeeks);
    }
}

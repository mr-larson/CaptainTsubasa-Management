<?php

namespace App\Services;

use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Facades\DB;

class AITrainingService
{
    // Stats pertinentes par poste
    private const STATS_BY_POSITION = [
        'GK'  => ['hand_save', 'punch_save', 'defense', 'block', 'stamina', 'speed'],
        'DEF' => ['defense', 'tackle', 'block', 'intercept', 'stamina', 'speed'],
        'MDF' => ['pass', 'intercept', 'tackle', 'defense', 'attack', 'stamina'],
        'MOF' => ['pass', 'dribble', 'attack', 'intercept', 'shot', 'stamina'],
        'ATT' => ['shot', 'dribble', 'attack', 'pass', 'speed', 'stamina'],
        // Fallback pour postes non reconnus
        'DEFAULT' => ['speed', 'attack', 'defense', 'pass', 'dribble', 'shot', 'tackle', 'intercept'],
    ];

    public function trainForWeek(GameSave $gameSave): void
    {
        $state = $gameSave->state ?? [];

        // Empêcher un double entraînement IA pour la même semaine
        $aiWeekKey = 'ai_training_week';
        if (($state[$aiWeekKey] ?? null) === $gameSave->week) {
            return;
        }

        $week   = $gameSave->week ?? 1;
        $season = $gameSave->season ?? 1;

        // Joueurs déjà entraînés manuellement cette semaine
        $manuallyTrainedIds = $this->getManuallyTrainedPlayerIds($state, $week, $season);

        // Toutes les équipes de la save
        $allTeams = GameTeam::where('game_save_id', $gameSave->id)
            ->with(['contracts.gamePlayer'])
            ->get();

        $controlledTeamId = $gameSave->controlled_game_team_id;
        $aiEntries        = [];

        foreach ($allTeams as $team) {
            $isControlled = ((int) $team->id === (int) $controlledTeamId);

            if ($isControlled) {
                // Équipe contrôlée : gain légèrement réduit, exclure joueurs déjà entraînés manuellement
                $results = $this->trainTeam($team, gainMax: 2, excludePlayerIds: $manuallyTrainedIds);
                // Stocker les résultats pour l'affichage dans TabTraining
                $aiEntries = $results;
            } else {
                $this->trainTeam($team, gainMax: 3);
            }
        }

        // Toujours mettre à jour state.training avec la semaine courante
        $training = $state['training'] ?? [];

        // Réinitialiser si nouvelle semaine
        if (
            !isset($training['season'], $training['week']) ||
            (int) $training['season'] !== $season ||
            (int) $training['week'] !== $week
        ) {
            $training = ['season' => $season, 'week' => $week, 'entries' => [], 'ai_entries' => []];
        }

        // Toujours écrire ai_entries (vide ou non)
        $training['ai_entries'] = $aiEntries;
        $state['training']      = $training;

        // Marquer cette semaine comme traitée
        $state[$aiWeekKey] = $week;
        $gameSave->state   = $state;
        $gameSave->save();
    }

    protected function trainTeam(GameTeam $team, int $gainMax = 3, array $excludePlayerIds = []): array
    {
        $contracts = $team->contracts->filter(fn($c) => $c->gamePlayer !== null);
        if ($contracts->isEmpty()) return [];

        $minStamina  = config('training.min_stamina_to_train', 10);
        $staminaCost = config('training.stamina_cost', 5);
        $statMin     = config('training.stat_min', 0);
        $statMax     = config('training.stat_max', 100);
        $gainMin     = 1;
        $trainCount  = rand(1, 3);

        // Priorité aux titulaires, sinon remplaçants
        $starterContracts = $contracts->where('is_starter', true);
        $subContracts     = $contracts->where('is_starter', false);

        // Pool de joueurs éligibles (stamina suffisante + pas exclus)
        $eligiblePool = $contracts->filter(fn($c) =>
            $c->gamePlayer->stamina >= $minStamina &&
            !in_array($c->gamePlayer->id, $excludePlayerIds)
        );

        if ($eligiblePool->isEmpty()) return [];

        // Garder un suivi des joueurs déjà entraînés ce tour pour éviter les doublons
        $trainedThisRound = [];
        $results          = [];

        for ($i = 0; $i < $trainCount; $i++) {
            // Pool disponible (pas déjà entraîné ce tour)
            $pool = $eligiblePool->filter(fn($c) =>
                !in_array($c->gamePlayer->id, $trainedThisRound) &&
                $c->gamePlayer->stamina >= $minStamina
            );

            // Préférer les titulaires
            $starterPool = $pool->filter(fn($c) => $c->is_starter);
            if ($starterPool->isNotEmpty()) {
                $pool = $starterPool;
            }

            if ($pool->isEmpty()) break;

            // Sélection avec variété :
            // 70% → joueur avec la plus grande faiblesse dans son poste
            // 30% → joueur aléatoire du pool (pour la variété)
            $useRandom = (rand(1, 10) <= 3);

            if ($useRandom) {
                $contract = $pool->random();
            } else {
                $contract = $pool
                    ->sortBy(fn($c) => $this->positionWeakness($c->gamePlayer))
                    ->first();
            }

            $player  = $contract->gamePlayer;
            $statKey = $this->bestStatToTrain($player);

            if (!$statKey) continue;

            $gainApplied = 0;
            DB::transaction(function () use ($player, $statKey, $gainMin, $gainMax, $staminaCost, $statMin, $statMax, &$gainApplied) {
                $gain       = rand($gainMin, $gainMax);
                $oldStat    = (int) ($player->{$statKey} ?? 0);
                $newStat    = min($statMax, max($statMin, $oldStat + $gain));
                $oldStamina = (int) $player->stamina;
                $newStamina = max($statMin, $oldStamina - $staminaCost);

                $player->{$statKey} = $newStat;
                $player->stamina    = $newStamina;
                $player->save();
                $gainApplied = $newStat - $oldStat;
            });

            $results[] = [
                'player_id'    => $player->id,
                'player_name'  => trim(($player->firstname ?? '') . ' ' . ($player->lastname ?? '')),
                'stat'         => $statKey,
                'gain'         => $gainApplied,
                'stamina_cost' => $staminaCost,
            ];

            $trainedThisRound[] = $player->id;
        }

        return $results;
    }

    // ==========================
    //   LOGIQUE DE SÉLECTION
    // ==========================

    /**
     * Retourne la "faiblesse pondérée" d'un joueur selon son poste.
     * Plus la valeur est basse, plus le joueur a besoin d'entraînement.
     */
    protected function positionWeakness($player): float
    {
        $stats = $this->relevantStatsForPosition($player->position ?? '');
        if (empty($stats)) return 100.0;

        $values = array_map(fn($k) => (int) ($player->{$k} ?? 0), $stats);
        return array_sum($values) / max(count($values), 1);
    }

    /**
     * Choisit la meilleure stat à entraîner pour ce joueur selon son poste.
     * Prend la stat la plus basse parmi les stats pertinentes du poste,
     * avec une légère randomisation (pas toujours la même).
     */
    protected function bestStatToTrain($player): ?string
    {
        $stats = $this->relevantStatsForPosition($player->position ?? '');
        if (empty($stats)) return null;

        // Calculer les valeurs actuelles
        $values = [];
        foreach ($stats as $k) {
            $val = (int) ($player->{$k} ?? 0);
            // Ignorer les stats déjà au max
            if ($val < 100) {
                $values[$k] = $val;
            }
        }

        if (empty($values)) return null;

        // Trier par valeur croissante
        asort($values);
        $sorted = array_keys($values);

        // 60% → stat la plus basse
        // 30% → 2e stat la plus basse
        // 10% → 3e stat la plus basse (si dispo)
        $roll = rand(1, 10);
        if ($roll <= 6 || count($sorted) < 2) {
            return $sorted[0];
        } elseif ($roll <= 9 || count($sorted) < 3) {
            return $sorted[1];
        } else {
            return $sorted[2];
        }
    }

    /**
     * Retourne les stats pertinentes pour un poste donné.
     */
    protected function relevantStatsForPosition(string $position): array
    {
        $pos = strtoupper(trim($position));

        // Correspondance flexible
        if (str_contains($pos, 'GK') || str_contains($pos, 'GOAL')) {
            return self::STATS_BY_POSITION['GK'];
        }
        if (str_contains($pos, 'DEF') || str_contains($pos, 'BACK')) {
            return self::STATS_BY_POSITION['DEF'];
        }
        if (str_contains($pos, 'MDF') || str_contains($pos, 'DEFENSIVE MID')) {
            return self::STATS_BY_POSITION['MDF'];
        }
        if (str_contains($pos, 'MOF') || str_contains($pos, 'MID') || str_contains($pos, 'MILIEU')) {
            return self::STATS_BY_POSITION['MOF'];
        }
        if (str_contains($pos, 'ATT') || str_contains($pos, 'FOR') || str_contains($pos, 'FORWARD')) {
            return self::STATS_BY_POSITION['ATT'];
        }

        return self::STATS_BY_POSITION['DEFAULT'];
    }

    // ==========================
    //   HELPERS
    // ==========================

    /**
     * Récupère les IDs des joueurs déjà entraînés manuellement cette semaine.
     */
    protected function getManuallyTrainedPlayerIds(array $state, int $week, int $season): array
    {
        $training = $state['training'] ?? null;

        if (
            !$training ||
            !isset($training['season'], $training['week']) ||
            (int) $training['season'] !== $season ||
            (int) $training['week'] !== $week
        ) {
            return [];
        }

        return array_column($training['entries'] ?? [], 'player_id');
    }

    /**
     * Conservé pour compatibilité — remplacé par positionWeakness().
     */
    protected function globalWeakness($player): float
    {
        return $this->positionWeakness($player);
    }

    /**
     * Conservé pour compatibilité — remplacé par bestStatToTrain().
     */
    protected function lowestRelevantStat($player): ?string
    {
        return $this->bestStatToTrain($player);
    }
}

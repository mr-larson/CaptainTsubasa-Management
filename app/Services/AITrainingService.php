<?php

namespace App\Services;

use App\Enums\TeamStyle;
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

    // Stats boostées en priorité par style tactique (bias -15 sur leur valeur apparente)
    private const STYLE_PRIORITY_STATS = [
        TeamStyle::TACTICAL_OFFENSIVE  => ['shot', 'dribble', 'attack', 'speed'],
        TeamStyle::TACTICAL_DEFENSIVE  => ['defense', 'tackle', 'block', 'intercept'],
        TeamStyle::TACTICAL_POSSESSION => ['pass', 'intercept', 'stamina', 'dribble'],
        TeamStyle::TACTICAL_COUNTER    => ['speed', 'shot', 'defense', 'tackle'],
        TeamStyle::TACTICAL_BALANCED   => [],
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
            ->with(['contracts' => fn($q) => $q->activeAt($week)->with('gamePlayer')])
            ->get();

        $controlledTeamIds = $gameSave->controlledGameTeamIds();
        $activeTeamId      = (int) $gameSave->controlled_game_team_id;
        $aiEntries         = [];

        $allEntries = []; // historique complet toutes équipes

        foreach ($allTeams as $team) {
            $isHuman = in_array((int) $team->id, $controlledTeamIds, true);
            if ($isHuman) {
                // Équipe humaine : auto-entraînement doux des joueurs non entraînés à la main.
                $results = $this->trainTeam($team, gainMax: 2, excludePlayerIds: $manuallyTrainedIds, tacticalStyle: $team->tactical_style, gameSave: $gameSave);
                // ai_entries = panneau du joueur actif uniquement.
                if ((int) $team->id === $activeTeamId) {
                    $aiEntries = $results;
                }
            } else {
                // Équipe IA : entraînement complet, mais chaque séance coûte de l'argent.
                $results = $this->trainTeam($team, gainMax: 3, tacticalStyle: $team->tactical_style, gameSave: $gameSave, chargeCost: true);
            }
            foreach ($results as $entry) {
                $allEntries[] = array_merge($entry, ['team_id' => $team->id, 'team_name' => $team->name]);
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

        // Historique cumulatif
        $history = $state['ai_training_history'] ?? [];
        foreach ($allEntries as $entry) {
            $history[] = array_merge($entry, [
                'season' => $season,
                'week'   => $week,
            ]);
        }
        $state['ai_training_history'] = $history;
        $state['training'] = $training;

        // Marquer cette semaine comme traitée
        $state[$aiWeekKey] = $week;
        $gameSave->state   = $state;
        $gameSave->save();
    }

    protected function trainTeam(GameTeam $team, int $gainMax = 3, array $excludePlayerIds = [], ?string $tacticalStyle = null, ?GameSave $gameSave = null, bool $chargeCost = false): array
    {
        $contracts = $team->contracts->filter(fn($c) => $c->gamePlayer !== null);
        if ($contracts->isEmpty()) return [];

        $minStamina   = $gameSave ? (int) $gameSave->getConfig('training_min_stamina') : config('training.min_stamina_to_train', 40);
        $staminaCost  = $gameSave ? (int) $gameSave->getConfig('training_stamina_cost') : config('training.stamina_cost', 5);
        $statMin      = config('training.stat_min', 0);
        $statMax      = config('training.stat_max', 100);
        $gainMin      = $gameSave ? (int) $gameSave->getConfig('training_gain_min') : 1;
        $trainingCost = ($chargeCost && $gameSave) ? (int) $gameSave->getConfig('training_cost') : 0;
        $trainCount   = rand(1, 3);

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

            // L'IA paie chaque séance ; si elle ne peut plus, elle arrête d'entraîner.
            if ($trainingCost > 0 && (int) ($team->budget ?? 0) < $trainingCost) break;

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
            $statKey = $this->bestStatToTrain($player, $tacticalStyle);

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

            // Débiter le budget de l'équipe pour cette séance.
            if ($trainingCost > 0) {
                $team->budget = max(0, (int) ($team->budget ?? 0) - $trainingCost);
                $team->save();
            }

            $results[] = [
                'player_id'    => $player->id,
                'player_name'  => trim(($player->firstname ?? '') . ' ' . ($player->lastname ?? '')),
                'stat'         => $statKey,
                'gain'         => $gainApplied,
                'stamina_cost' => $staminaCost,
                'cost'         => $trainingCost,
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
     * Applique un biais vers les stats alignées au style tactique de l'équipe.
     */
    protected function bestStatToTrain($player, ?string $tacticalStyle = null): ?string
    {
        $stats = $this->relevantStatsForPosition($player->position ?? '');
        if (empty($stats)) return null;

        // Stats prioritaires selon le style tactique
        $priorityStats = self::STYLE_PRIORITY_STATS[$tacticalStyle] ?? [];
        $styleBias     = 15; // Points soustraits aux stats prioritaires (les rend "plus basses" → plus ciblées)

        // Calculer les valeurs actuelles avec biais de style
        $values = [];
        foreach ($stats as $k) {
            $val = (int) ($player->{$k} ?? 0);
            if ($val >= 100) continue;

            // Appliquer le biais : les stats alignées au style paraissent plus basses
            if (in_array($k, $priorityStats)) {
                $val = max(0, $val - $styleBias);
            }

            $values[$k] = $val;
        }

        if (empty($values)) return null;

        // Trier par valeur croissante (biaisée)
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

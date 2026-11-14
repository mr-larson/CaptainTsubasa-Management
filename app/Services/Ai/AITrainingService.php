<?php

namespace App\Services\Ai;

use App\Contracts\Randomizer;
use App\Enums\TeamStyle;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Facades\DB;
use App\Services\Players\StaminaService;

/**
 * Entraînement automatique des équipes gérées par l'IA.
 * Chaque semaine, chaque équipe IA fait quelques séances : un joueur est choisi, une de ses stats progresse et il perd de l'endurance.
 * Chaque séance coûte de l'argent à l'équipe. Les équipes humaines ne sont jamais entraînées ici (seulement par leurs entraînements manuels).
 */
class AITrainingService
{

    /** Reçoit le générateur aléatoire (injectable pour rendre les tirages déterministes en test). */
    public function __construct(private Randomizer $random)
    {
    }
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
        TeamStyle::TACTICAL_DEFENSIVE  => ['defense', 'tackle', 'block', 'intercept', 'hand_save', 'punch_save'],
        TeamStyle::TACTICAL_POSSESSION => ['pass', 'intercept', 'stamina', 'dribble'],
        TeamStyle::TACTICAL_COUNTER    => ['speed', 'shot', 'defense', 'tackle'],
        TeamStyle::TACTICAL_BALANCED   => [],
    ];

    /**
     * Lance l'entraînement de toutes les équipes IA pour la semaine courante de la partie.
     * Ignore les joueurs blessés, suspendus ou en formation de poste. Les joueurs qui ont joué la semaine sont pris en compte pour l'endurance à préserver.
     * Enregistre les séances dans l'historique de la saison (état de la partie), marque la semaine comme traitée (pas de double entraînement) puis sauvegarde la partie.
     */
    public function trainForWeek(GameSave $gameSave): void
    {
        $state = $gameSave->gameState();

        // Empêcher un double entraînement IA pour la même semaine
        if ($state->aiTrainingWeek() === $gameSave->week) {
            return;
        }

        $week   = $gameSave->week ?? 1;
        $season = $gameSave->season ?? 1;
        $unavailableIds = GameInjury::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $week)->pluck('game_player_id')
            ->merge(GameSanction::where('game_save_id', $gameSave->id)->where('week_return', '>', $week)
                ->where('weeks_suspended', '>', 0)->pluck('game_player_id'))
            ->merge(GamePositionTraining::unavailablePlayerIds($gameSave->id, $week))->all();

        // Toutes les équipes de la save
        $allTeams = GameTeam::where('game_save_id', $gameSave->id)
            ->with(['contracts' => fn($q) => $q->activeAt($week)->with('gamePlayer')])
            ->get();

        $controlledTeamIds = [...$gameSave->controlledGameTeamIds(), (int) $gameSave->controlled_game_team_id];
        $playedByTeam = [];
        $playedMatches = GameMatch::where('game_save_id', $gameSave->id)->where('week', $week)->where('status', 'played')
            ->with('playerStats')
            ->get(['id', 'game_save_id', 'home_team_id', 'away_team_id', 'match_stats']);
        foreach ($playedMatches as $match) {
            $played = array_fill_keys(StaminaService::resolvePlayedPlayerIds($match), true);
            foreach ([$match->home_team_id, $match->away_team_id] as $teamId) {
                $playedByTeam[$teamId] = ($playedByTeam[$teamId] ?? []) + $played;
            }
        }
        $aiEntries         = []; // jamais d'auto-entraînement humain → toujours vide

        $allEntries = []; // historique complet toutes équipes

        foreach ($allTeams as $team) {
            // Les équipes humaines ne sont jamais auto-entraînées : seuls leurs
            // entraînements manuels font progresser leurs joueurs. L'auto-entraînement
            // est réservé aux équipes IA.
            if (in_array((int) $team->id, $controlledTeamIds, true)) {
                continue;
            }

            // Équipe IA : entraînement complet, mais chaque séance coûte de l'argent.
            $results = $this->trainTeam(
                $team,
                gainMax: (int) $gameSave->getConfig('training_gain_max'),
                excludePlayerIds: $unavailableIds,
                tacticalStyle: $team->tactical_style,
                gameSave: $gameSave,
                chargeCost: true,
                played: $playedByTeam[$team->id] ?? null,
            );

            foreach ($results as $entry) {
                $allEntries[] = array_merge($entry, ['team_id' => $team->id, 'team_name' => $team->name]);
            }
        }

        // Toujours mettre à jour state.training avec la semaine courante
        // (réinitialisée si nouvelle semaine) et écrire ai_entries (vide ou non).
        $state->setAiTraining((int) $season, (int) $week, $aiEntries);

        // Historique de la saison en cours (seul affiché par l'onglet
        // Entraînement) : les saisons passées sont purgées pour borner le state.
        $history = $state->aiTrainingHistoryOfSeason((int) $season);
        foreach ($allEntries as $entry) {
            $history[] = array_merge($entry, [
                'season' => $season,
                'week'   => $week,
            ]);
        }
        $state->putAiTrainingHistory($history);

        // Marquer cette semaine comme traitée
        $state->markAiTrainingWeek($week);
        $gameSave->save();
    }

    /**
     * Fait s'entraîner une équipe IA et retourne la liste des séances réalisées (joueur, stat, gain, coût).
     * Le nombre de séances est limité par la config hebdomadaire moins celles déjà faites. Les joueurs éligibles doivent garder assez d'endurance (réserve selon titulaire, repos ou match joué).
     * Priorité : gardiens reposés, joueurs en rotation, puis titulaires ou remplaçants selon la philosophie de l'équipe. 70 % du temps on prend le plus faible de son poste, 30 % un joueur au hasard.
     * Chaque séance augmente la stat choisie, retire de l'endurance au joueur (sauvegardé en base) et débite le budget de l'équipe, écrit une seule fois en fin de boucle. S'arrête si le budget est insuffisant.
     */
    protected function trainTeam(GameTeam $team, int $gainMax = 3, array $excludePlayerIds = [], ?string $tacticalStyle = null, ?GameSave $gameSave = null, bool $chargeCost = false, ?array $played = null): array
    {
        $contracts = $team->contracts->filter(fn($c) => $c->gamePlayer !== null);
        if ($contracts->isEmpty()) return [];

        $minStamina   = $gameSave ? (int) $gameSave->getConfig('training_min_stamina') : config('training.min_stamina_to_train', 40);
        $staminaCost  = $gameSave ? (int) $gameSave->getConfig('training_stamina_cost') : config('training.stamina_cost', 5);
        $statMin      = config('training.stat_min', 0);
        $statMax      = config('training.stat_max', 100);
        $gainMin      = $gameSave ? (int) $gameSave->getConfig('training_gain_min') : 1;
        $trainingCost = ($chargeCost && $gameSave) ? (int) $gameSave->getConfig('training_cost') : 0;
        $trainCount   = $gameSave ? (int) $gameSave->getConfig('training_max_per_week') : 3;
        if ($gameSave) {
            $trainCount -= collect($gameSave->gameState()->weekTrainingEntries((int) $gameSave->season, (int) $gameSave->week))
                ->where('team_id', $team->id)->count();
        }

        // À la clôture, l'entraînement précède la récupération et le coût du match.
        $stars = $team->management_philosophy === TeamStyle::PHILOSOPHY_STARS;
        $starterReserve = $team->management_philosophy === TeamStyle::PHILOSOPHY_ECONOMIST ? 80 : 75;
        $restRecovery = $gameSave ? (int) $gameSave->getConfig('rest_stamina_recovery') : 0;
        $matchCost = $gameSave ? (int) $gameSave->getConfig('match_stamina_cost') : 0;
        $restingRoles = $gameSave
            ? $gameSave->gameState()->restingRoles($team->id, (int) $gameSave->season, (int) $gameSave->week)
            : [];
        $rested = fn ($c) => $played !== null && !isset($played[$c->game_player_id]);
        $isKeeper = fn ($c) => $this->relevantStatsForPosition($c->gamePlayer->position ?? '') === self::STATS_BY_POSITION['GK'];

        $eligiblePool = $contracts->filter(function ($c) use ($played, $rested, $starterReserve, $matchCost, $restRecovery, $staminaCost, $minStamina, $excludePlayerIds) {
            $reserve = $played === null
                ? ($c->is_starter ? $starterReserve : 0)
                : ($rested($c) ? max(50, 70 - $restRecovery) : $starterReserve + $matchCost);
            return $c->gamePlayer->stamina >= max($minStamina, $reserve + $staminaCost)
                && !in_array($c->game_player_id, $excludePlayerIds);
        });

        $trainedThisRound = [];
        $results = [];
        $trainedKeeper = false;
        $budgetSpent = false;

        while (count($results) < $trainCount) {
            $pool = $eligiblePool->reject(fn ($c) =>
                in_array($c->game_player_id, $trainedThisRound, true) || ($trainedKeeper && $isKeeper($c)));
            $restedKeepers = $pool->filter(fn ($c) => $rested($c) && $isKeeper($c));
            $rotated = $pool->filter(fn ($c) => $rested($c) && isset($restingRoles[$c->game_player_id]));
            $preferred = $pool->filter(fn ($c) => $played !== null
                ? $rested($c) || ($stars && $c->is_starter)
                : (bool) $c->is_starter === $stars);

            foreach ([$restedKeepers, $rotated, $preferred] as $priority) {
                if ($priority->isNotEmpty()) {
                    $pool = $priority;
                    break;
                }
            }

            if ($pool->isEmpty()) break;

            // L'IA paie chaque séance ; si elle ne peut plus, elle arrête d'entraîner.
            if ($trainingCost > 0 && (int) ($team->budget ?? 0) < $trainingCost) break;

            // Sélection avec variété :
            // 70% → joueur avec la plus grande faiblesse dans son poste
            // 30% → joueur aléatoire du pool (pour la variété)
            $useRandom = ($this->random->int(1, 10) <= 3);

            if ($useRandom) {
                $contract = $this->random->pick($pool->all());
            } else {
                $contract = $pool
                    ->sortBy(fn($c) => $this->positionWeakness($c->gamePlayer))
                    ->first();
            }

            $player  = $contract->gamePlayer;
            $trainedThisRound[] = $player->id;
            $role = $rested($contract) ? ($restingRoles[$player->id] ?? null) : null;
            $statKey = $this->bestStatToTrain($player, $tacticalStyle, $role);

            if (!$statKey) continue;

            $gainApplied = 0;
            DB::transaction(function () use ($player, $statKey, $gainMin, $gainMax, $staminaCost, $statMin, $statMax, &$gainApplied) {
                $gain       = $this->random->int($gainMin, $gainMax);
                $oldStat    = (int) ($player->{$statKey} ?? 0);
                $newStat    = min($statMax, max($statMin, $oldStat + $gain));
                $oldStamina = (int) $player->stamina;
                $newStamina = max($statMin, $oldStamina - $staminaCost);

                $player->{$statKey} = $newStat;
                $player->stamina    = $newStamina;
                $player->save();
                $gainApplied = $newStat - $oldStat;
            });

            // Débiter le budget de l'équipe pour cette séance : en mémoire (la
            // garde ci-dessus le relit), écrit une seule fois après la boucle.
            if ($trainingCost > 0) {
                $team->budget = max(0, (int) ($team->budget ?? 0) - $trainingCost);
                $budgetSpent = true;
            }

            $results[] = [
                'player_id'    => $player->id,
                'player_name'  => trim(($player->firstname ?? '') . ' ' . ($player->lastname ?? '')),
                'stat'         => $statKey,
                'gain'         => $gainApplied,
                'stamina_cost' => $staminaCost,
                'cost'         => $trainingCost,
            ];

            $trainedKeeper = $trainedKeeper || $isKeeper($contract);
        }

        if ($budgetSpent) {
            $team->save();
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
    protected function bestStatToTrain($player, ?string $tacticalStyle = null, ?string $role = null): ?string
    {
        $stats = $this->relevantStatsForPosition($role ?? $player->position ?? '');
        if ($stats === self::STATS_BY_POSITION['GK'] && $tacticalStyle === TeamStyle::TACTICAL_POSSESSION) {
            $stats[] = 'pass';
        }
        if (empty($stats)) return null;

        // Stats prioritaires selon le style tactique
        $priorityStats = self::STYLE_PRIORITY_STATS[$tacticalStyle] ?? [];
        $styleBias     = 15; // Points soustraits aux stats prioritaires (les rend "plus basses" → plus ciblées)

        // Calculer les valeurs actuelles avec biais de style
        $values = [];
        foreach ($stats as $k) {
            $val = (int) ($player->{$k} ?? 0);
            // Une fois le niveau visé atteint, le club travaille un autre point.
            $target = in_array($k, $priorityStats, true) ? 60 : 55;
            if ($val >= $target || $k === 'stamina') continue;

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
        $roll = $this->random->int(1, 10);
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

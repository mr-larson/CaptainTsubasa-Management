<?php

namespace App\Services;

use App\Enums\TeamStyle;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AITransferService
{
    // Effectif cible idéal
    private const TARGET_SQUAD = 18;
    private const SQUAD_EMERGENCY = 11; // 100% budget
    private const SQUAD_LOW       = 13; // 85% budget
    // Sinon : 70% budget

    // Max recrutements par semaine par équipe
    private const MAX_SIGNINGS_PER_WEEK = 2;

    // Seuil de stamina moyenne des titulaires pour recruter en urgence fatigue
    private const FATIGUE_THRESHOLD = 37;

    // Stats pour l'overall
    private const OVERALL_KEYS = [
        'speed', 'stamina', 'attack', 'defense',
        'shot', 'pass', 'dribble', 'block', 'intercept', 'tackle',
    ];

    // Composition cible par groupe de poste
    // [titulaires, remplaçants] => total cible
    private const TARGET_BY_POSITION = [
        'GK'  => 2,  // 1 tit + 1 remplaçant
        'DEF' => 6,  // 4 tit + 2 remplaçants
        'MID' => 6,  // 4 tit + 2 remplaçants
        'ATT' => 4,  // 2 tit + 2 remplaçants
    ];

    // Priorité de recrutement par poste (plus bas = plus urgent)
    private const POSITION_PRIORITY = [
        'GK'  => 1,
        'DEF' => 2,
        'MID' => 2,
        'ATT' => 2,
    ];

    public function recruitForWeek(GameSave $gameSave): void
    {
        $week           = $gameSave->week ?? 1;
        $seasonLength   = $this->getSeasonLength($gameSave);
        $remainingWeeks = max(1, $seasonLength - $week + 1);
        $controlledTeamIds = $gameSave->controlledGameTeamIds();

        $aiTeams = GameTeam::where('game_save_id', $gameSave->id)
            ->when($controlledTeamIds, fn($q) => $q->whereNotIn('id', $controlledTeamIds))
            ->with(['contracts' => fn($q) => $q->activeAt($week)->with('gamePlayer')])
            ->get();

        $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts', fn($q) => $q->where('game_save_id', $gameSave->id)->activeAt($week))
            ->get();

        if ($freePlayers->isEmpty()) return;

        $injuredIds = GameInjury::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $week)
            ->pluck('game_player_id')->toArray();

        $suspendedIds = GameSanction::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $week)
            ->where('weeks_suspended', '>', 0)
            ->pluck('game_player_id')->toArray();

        $unavailableIds = array_unique(array_merge($injuredIds, $suspendedIds));

        foreach ($aiTeams as $team) {
            $this->recruitForTeam($team, $freePlayers, $gameSave, $remainingWeeks, $unavailableIds);

            // Rafraîchir les joueurs libres après chaque équipe
            $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
                ->whereDoesntHave('contracts', fn($q) => $q->where('game_save_id', $gameSave->id)->activeAt($week))
                ->get();

            if ($freePlayers->isEmpty()) break;
        }
    }

    protected function recruitForTeam(
        GameTeam $team,
        Collection $freePlayers,
        GameSave $gameSave,
        int $remainingWeeks,
        array $unavailableIds = []
    ): void {
        $contracts    = $team->contracts->filter(fn($c) => $c->gamePlayer !== null);
        $squadPlayers = $contracts->map(fn($c) => $c->gamePlayer)->filter();
        $squadSize    = $squadPlayers->count();
        $starterCount = $contracts->where('is_starter', true)->count();
        $budget       = $team->budget ?? 0;

        // Ne jamais dépasser 18 joueurs
        if ($squadSize >= self::TARGET_SQUAD) return;

        // Calcul du budget disponible selon taille effectif
        $budgetRatio = match(true) {
            $squadSize < self::SQUAD_EMERGENCY => 1.0,
            $squadSize < self::SQUAD_LOW       => 0.85,
            default                            => 0.70,
        };
        $availableBudget = (int) floor($budget * $budgetRatio);

        if ($availableBudget <= 0) return;

        // Construire la liste de priorités de recrutement
        $needs = $this->buildRecruitmentNeeds(
            $contracts, $squadPlayers, $squadSize, $unavailableIds
        );

        if (empty($needs)) return;

        $signingsMade = 0;

        foreach ($needs as $need) {
            if ($signingsMade >= self::MAX_SIGNINGS_PER_WEEK) break;
            if ($availableBudget <= 0) break;
            if ($squadSize >= self::TARGET_SQUAD) break;

            $candidate = $this->findBestCandidate(
                $freePlayers,
                $need['position_group'],
                $squadPlayers,
                $availableBudget,
                $remainingWeeks,
                $team->management_philosophy
            );

            if (!$candidate) continue;

            $salary    = min($candidate->adjusted_cost, (int) ($availableBudget / max($remainingWeeks, 1)));
            $salary    = max(0, $salary);
            $totalCost = $salary * $remainingWeeks;

            if ($totalCost > $availableBudget && $salary > 0) continue;

            $isStarter = $starterCount < 11;

            DB::transaction(function () use ($gameSave, $team, $candidate, $salary, $remainingWeeks, $isStarter) {
                GameContract::create([
                    'game_save_id'                    => $gameSave->id,
                    'game_team_id'                    => $team->id,
                    'game_player_id'                  => $candidate->id,
                    'salary'                          => $salary,
                    'start_week'                      => $gameSave->week ?? 1,
                    'end_week'                        => ($gameSave->week ?? 1) + $remainingWeeks - 1,
                    'is_starter'                      => $isStarter,
                    'is_captain'                      => false,
                    'captain_rerolls_remaining'       => 3,
                    'captain_reroll_used_this_action' => false,
                ]);

                $team->budget = max(0, ($team->budget ?? 0) - ($salary * $remainingWeeks));
                $team->save();
            });

            $squadPlayers->push($candidate);
            $availableBudget -= $totalCost;
            $budget          -= $totalCost;
            $squadSize++;
            $signingsMade++;
            $starterCount += $isStarter ? 1 : 0;
            $freePlayers = $freePlayers->reject(fn($p) => $p->id === $candidate->id);
        }
    }

    // ==========================
    //   ANALYSE DES BESOINS
    // ==========================

    /**
     * Construit une liste ordonnée de besoins de recrutement.
     * Prend en compte :
     * - La composition cible par poste
     * - Les indisponibles (blessés/suspendus)
     * - La fatigue accrue des titulaires
     */
    protected function buildRecruitmentNeeds(
        Collection $contracts,
        Collection $squadPlayers,
        int $squadSize,
        array $unavailableIds
    ): array {
        $needs = [];

        // Compter les joueurs par poste
        $countByPos = ['GK' => 0, 'DEF' => 0, 'MID' => 0, 'ATT' => 0];
        foreach ($squadPlayers as $p) {
            $g = $this->positionGroup($p->position ?? '');
            if (isset($countByPos[$g])) $countByPos[$g]++;
        }

        // Priorité 1 : postes manquants par rapport à la cible
        foreach (self::TARGET_BY_POSITION as $pos => $target) {
            $current = $countByPos[$pos] ?? 0;
            if ($current < $target) {
                $missing  = $target - $current;
                $priority = self::POSITION_PRIORITY[$pos] ?? 3;
                // GK remplaçant manquant = priorité absolue
                if ($pos === 'GK' && $current === 0) $priority = 0; // pas de GK du tout
                if ($pos === 'GK' && $current === 1) $priority = 1; // GK mais pas de remplaçant
                for ($i = 0; $i < $missing; $i++) {
                    $needs[] = ['position_group' => $pos, 'priority' => $priority];
                }
            }
        }

        // Priorité 2 : titulaires indisponibles → recruter même poste
        $unavailableStarters = $contracts->filter(fn($c) =>
            $c->is_starter && in_array($c->gamePlayer?->id, $unavailableIds)
        );
        foreach ($unavailableStarters as $c) {
            $pos = $this->positionGroup($c->gamePlayer->position ?? '');
            // Seulement si pas déjà couvert par un remplaçant au même poste
            $subs = $contracts->filter(fn($sub) =>
                !$sub->is_starter &&
                $this->positionGroup($sub->gamePlayer?->position ?? '') === $pos
            );
            if ($subs->isEmpty()) {
                $needs[] = ['position_group' => $pos, 'priority' => 1];
            }
        }

        // Priorité 3 : fatigue accrue des titulaires → recruter si < seuil
        $starters = $contracts->where('is_starter', true)
            ->filter(fn($c) => $c->gamePlayer !== null);

        $avgStaminaPercent = $starters->isEmpty()
            ? 100
            : (float) $starters->avg(fn($c) => $c->gamePlayer->stamina ?? 100);

        if ($avgStaminaPercent <= self::FATIGUE_THRESHOLD && $squadSize < self::TARGET_SQUAD) {
            // Trouver le poste dont la stamina moyenne est la plus basse
            $mostFatiguedPos = $starters
                ->groupBy(fn($c) => $this->positionGroup($c->gamePlayer->position ?? ''))
                ->map(fn($group) => (float) $group->avg(fn($c) => $c->gamePlayer->stamina ?? 100))
                ->sortBy(fn($avg) => $avg) // ASC : le plus bas d'abord
                ->keys()
                ->first();

            if ($mostFatiguedPos) {
                $needs[] = ['position_group' => $mostFatiguedPos, 'priority' => 4];
            }
        }

        if (empty($needs)) return [];

        // Dédupliquer et trier par priorité
        usort($needs, fn($a, $b) => $a['priority'] - $b['priority']);

        return $needs;
    }

    // ==========================
    //   SÉLECTION DU CANDIDAT
    // ==========================
    protected function findBestCandidate(
        Collection $freePlayers,
        string $positionGroup,
        Collection $squadPlayers,
        int $budget,
        int $remainingWeeks,
        ?string $philosophy = null
    ): ?GamePlayer {
        $teamAvgOverall = $this->avgOverall($squadPlayers);

        $candidates = $freePlayers->filter(function ($p) use ($positionGroup, $budget, $remainingWeeks) {
            if ($positionGroup !== 'ANY' && $this->positionGroup($p->position ?? '') !== $positionGroup) {
                return false;
            }
            $salary    = min($p->adjusted_cost, (int) ($budget / max($remainingWeeks, 1)));
            $totalCost = $salary * $remainingWeeks;
            return $totalCost <= $budget;
        });

        if ($candidates->isEmpty() && $positionGroup !== 'ANY') {
            return $this->findBestCandidate($freePlayers, 'ANY', $squadPlayers, $budget, $remainingWeeks, $philosophy);
        }

        if ($candidates->isEmpty()) return null;

        return match ($philosophy) {
            // Stars : le meilleur joueur absolu, peu importe le prix
            TeamStyle::PHILOSOPHY_STARS => $candidates
                ->sortByDesc(fn($p) => $this->overallOf($p))
                ->first(),

            // Collectif : le plus proche de la moyenne équipe (homogénéité)
            TeamStyle::PHILOSOPHY_COLLECTIVE => $candidates
                ->sortBy(fn($p) => abs($this->overallOf($p) - $teamAvgOverall))
                ->first(),

            // Économe : meilleur ratio overall / coût (bonnes affaires)
            TeamStyle::PHILOSOPHY_ECONOMIST => $candidates
                ->sortByDesc(fn($p) => $this->overallOf($p) / max(1, $p->cost ?? 1))
                ->first(),

            // Équilibré : préfère légèrement au-dessus de la moyenne, sans excès
            default => $candidates
                ->sortByDesc(fn($p) => $this->scoreBalanced($p, $teamAvgOverall))
                ->first(),
        };
    }

    // ==========================
    //   HELPERS
    // ==========================

    protected function positionGroup(string $position): string
    {
        $p = strtoupper(trim($position));
        if (str_contains($p, 'GK') || str_contains($p, 'GOAL'))    return 'GK';
        if (str_contains($p, 'DEF') || str_contains($p, 'BACK'))   return 'DEF';
        if (str_contains($p, 'MDF') || str_contains($p, 'MID') || str_contains($p, 'MOF')) return 'MID';
        if (str_contains($p, 'ATT') || str_contains($p, 'FOR'))    return 'ATT';
        return 'MID';
    }

    protected function overallOf(GamePlayer $player): float
    {
        $values = array_map(fn($k) => (int) ($player->{$k} ?? 0), self::OVERALL_KEYS);
        $values = array_filter($values, fn($v) => $v > 0);
        if (empty($values)) return 0;
        return array_sum($values) / count($values);
    }

    protected function avgOverall(Collection $players): float
    {
        if ($players->isEmpty()) return 0;
        return $players->avg(fn($p) => $this->overallOf($p)) ?? 0;
    }

    protected function getSeasonLength(GameSave $gameSave): int
    {
        $teamCount = GameTeam::where('game_save_id', $gameSave->id)->count();
        if ($teamCount < 2) return 28;
        return $teamCount % 2 === 1 ? $teamCount * 2 : ($teamCount - 1) * 2;
    }

    /**
     * Score de sélection "Équilibré" : favorise les joueurs légèrement
     * au-dessus de la moyenne, pénalise les trop chers ou trop faibles.
     */
    protected function scoreBalanced(GamePlayer $player, float $teamAvg): float
    {
        $overall = $this->overallOf($player);
        $cost    = max(1, $player->cost ?? 1);

        // Bonus si au-dessus de la moyenne (plafonné à +20)
        $aboveAvg = min(20, max(0, $overall - $teamAvg));

        // Score : overall + bonus moyenne - pénalité coût
        return $overall + ($aboveAvg * 2) - ($cost / 50);
    }
}

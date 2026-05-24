<?php

namespace App\Services;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AITransferService
{
    // Nombre max de recrutements IA par semaine et par équipe
    private const MAX_SIGNINGS_PER_WEEK = 2;

    // Seuil d'effectif minimum — en dessous l'équipe recrute en priorité
    private const MIN_SQUAD_SIZE = 11;

    // Stats pour calculer l'overall d'un joueur
    private const OVERALL_KEYS = [
        'speed', 'stamina', 'attack', 'defense',
        'shot', 'pass', 'dribble', 'block', 'intercept', 'tackle',
    ];

    public function recruitForWeek(GameSave $gameSave): void
    {
        $week   = $gameSave->week ?? 1;
        $season = $gameSave->season ?? 1;

        // Calcul de la durée de saison pour les contrats
        $seasonLength = $this->getSeasonLength($gameSave);
        $remainingWeeks = max(1, $seasonLength - $week + 1);

        // Équipes IA (toutes sauf l'équipe contrôlée)
        $controlledTeamId = $gameSave->controlled_game_team_id;

        $aiTeams = GameTeam::where('game_save_id', $gameSave->id)
            ->when($controlledTeamId, fn($q) => $q->where('id', '!=', $controlledTeamId))
            ->with(['contracts.gamePlayer'])
            ->get();

        // Joueurs libres disponibles (sans contrat dans cette save)
        $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts', fn($q) => $q->where('game_save_id', $gameSave->id))
            ->get();

        if ($freePlayers->isEmpty()) return;

        foreach ($aiTeams as $team) {
            $this->recruitForTeam($team, $freePlayers, $gameSave, $remainingWeeks);

            // Rafraîchir la liste des joueurs libres après chaque équipe
            $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
                ->whereDoesntHave('contracts', fn($q) => $q->where('game_save_id', $gameSave->id))
                ->get();

            if ($freePlayers->isEmpty()) break;
        }
    }

    protected function recruitForTeam(
        GameTeam $team,
        Collection $freePlayers,
        GameSave $gameSave,
        int $remainingWeeks
    ): void {
        $contracts    = $team->contracts->filter(fn($c) => $c->gamePlayer !== null);
        $squadPlayers = $contracts->map(fn($c) => $c->gamePlayer)->filter();
        $squadSize    = $squadPlayers->count();
        $starterCount = $contracts->where('is_starter', true)->count();

        // Analyse des besoins
        $needs = $this->analyzeNeeds($squadPlayers, $squadSize, $starterCount);

        if (empty($needs)) return;

        $budget        = $team->budget ?? 0;
        $signingsMade  = 0;

        foreach ($needs as $need) {
            if ($signingsMade >= self::MAX_SIGNINGS_PER_WEEK) break;
            if ($budget <= 0) break;

            // Trouver le meilleur joueur libre pour ce besoin
            $candidate = $this->findBestCandidate(
                $freePlayers,
                $need['position_group'],
                $squadPlayers,
                $budget,
                $remainingWeeks
            );

            if (!$candidate) continue;

            // Calculer le salaire offert (basé sur le coût du joueur, ajusté au budget)
            $salary     = min($candidate->cost ?? 0, (int) ($budget / max($remainingWeeks, 1)));
            $salary     = max(0, $salary);
            $totalCost  = $salary * $remainingWeeks;

            if ($totalCost > $budget && $salary > 0) continue;

            // Signer le contrat
            $isStarter = $starterCount < 11;

            DB::transaction(function () use ($gameSave, $team, $candidate, $salary, $remainingWeeks, $isStarter) {
                GameContract::create([
                    'game_save_id'   => $gameSave->id,
                    'game_team_id'   => $team->id,
                    'game_player_id' => $candidate->id,
                    'salary'         => $salary,
                    'start_week'     => $gameSave->week ?? 1,
                    'end_week'       => ($gameSave->week ?? 1) + $remainingWeeks - 1,
                    'is_starter'     => $isStarter,
                ]);

                // Déduire du budget
                $team->budget = max(0, ($team->budget ?? 0) - ($salary * $remainingWeeks));
                $team->save();
            });

            // Mettre à jour le tracking local
            $squadPlayers->push($candidate);
            $budget -= $totalCost;
            $signingsMade++;
            $starterCount += $isStarter ? 1 : 0;

            // Retirer ce joueur de la liste des libres pour les prochaines équipes
            $freePlayers = $freePlayers->reject(fn($p) => $p->id === $candidate->id);
        }
    }

    // ==========================
    //   ANALYSE DES BESOINS
    // ==========================

    /**
     * Retourne une liste de besoins prioritaires pour l'équipe.
     * Chaque besoin = ['position_group' => 'GK'|'DEF'|'MID'|'ATT', 'priority' => int]
     */
    protected function analyzeNeeds(Collection $players, int $squadSize, int $starterCount): array
    {
        $needs = [];

        // Besoin 1 : effectif trop petit
        if ($squadSize < self::MIN_SQUAD_SIZE) {
            // Cherche le poste le plus manquant
            $missing = $this->getMissingPositions($players);
            foreach ($missing as $pos => $count) {
                $needs[] = ['position_group' => $pos, 'priority' => 10 + $count];
            }
            return $this->sortNeeds($needs);
        }

        // Besoin 2 : pas assez de titulaires (< 11)
        if ($starterCount < 11) {
            $missing = $this->getMissingPositions($players);
            foreach ($missing as $pos => $count) {
                $needs[] = ['position_group' => $pos, 'priority' => 5 + $count];
            }
        }

        // Besoin 3 : overall de l'équipe trop bas (< 45 de moyenne)
        $avgOverall = $this->avgOverall($players);
        if ($avgOverall < 45 && $needs === []) {
            // Recrute un joueur fort peu importe le poste
            $needs[] = ['position_group' => 'ANY', 'priority' => 3];
        }

        return $this->sortNeeds($needs);
    }

    protected function getMissingPositions(Collection $players): array
    {
        $counts = ['GK' => 0, 'DEF' => 0, 'MID' => 0, 'ATT' => 0];

        foreach ($players as $p) {
            $g = $this->positionGroup($p->position ?? '');
            if (isset($counts[$g])) $counts[$g]++;
        }

        $ideal  = ['GK' => 1, 'DEF' => 4, 'MID' => 4, 'ATT' => 2];
        $missing = [];

        foreach ($ideal as $pos => $target) {
            if ($counts[$pos] < $target) {
                $missing[$pos] = $target - $counts[$pos];
            }
        }

        return $missing;
    }

    protected function sortNeeds(array $needs): array
    {
        usort($needs, fn($a, $b) => $b['priority'] - $a['priority']);
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
        int $remainingWeeks
    ): ?GamePlayer {
        $teamAvgOverall = $this->avgOverall($squadPlayers);

        $candidates = $freePlayers->filter(function ($p) use ($positionGroup, $budget, $remainingWeeks) {
            // Filtre poste
            if ($positionGroup !== 'ANY' && $this->positionGroup($p->position ?? '') !== $positionGroup) {
                return false;
            }
            // Filtre budget : le coût total ne dépasse pas le budget
            $salary    = min($p->cost ?? 0, (int) ($budget / max($remainingWeeks, 1)));
            $totalCost = $salary * $remainingWeeks;
            return $totalCost <= $budget;
        });

        if ($candidates->isEmpty()) {
            // Si aucun candidat au bon poste, chercher n'importe quel poste
            if ($positionGroup !== 'ANY') {
                return $this->findBestCandidate($freePlayers, 'ANY', $squadPlayers, $budget, $remainingWeeks);
            }
            return null;
        }

        // Choisir le joueur dont l'overall est le plus proche de la moyenne de l'équipe
        // (évite de sur-recruter ou de prendre des joueurs trop faibles)
        return $candidates
            ->sortBy(fn($p) => abs($this->overallOf($p) - $teamAvgOverall))
            ->first();
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
        return 'MID'; // fallback
    }

    protected function overallOf(GamePlayer $player): float
    {
        $values = array_map(
            fn($k) => (int) ($player->{$k} ?? 0),
            self::OVERALL_KEYS
        );
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
}

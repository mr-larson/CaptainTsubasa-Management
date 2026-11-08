<?php

namespace App\Services;

use App\Enums\TeamStyle;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\Concerns\EvaluatesPlayers;
use Illuminate\Support\Collection;

class AiLineUpService
{
    use EvaluatesPlayers;

    /**
     * Seuils de stamina par philosophie.
     * En dessous du seuil, l'IA préfère faire tourner.
     */
    private const STAMINA_THRESHOLDS = [
        TeamStyle::PHILOSOPHY_STARS      => 45,  // Pousse les stars, rotate seulement si épuisé
        TeamStyle::PHILOSOPHY_COLLECTIVE => 65,  // Rotation régulière
        TeamStyle::PHILOSOPHY_BALANCED   => 60,  // Mix raisonnable
        TeamStyle::PHILOSOPHY_ECONOMIST  => 70,  // Rotation agressive, préserve tout le monde
    ];

    /**
     * Ajuste le lineup de toutes les équipes IA avant un match.
     * 1) Réévalue les titulaires selon philosophie + stamina
     * 2) Remplace les indisponibles (blessés/suspendus)
     */
    public function adjustLineupsForWeek(GameSave $gameSave, ?array $teamIds = null): void
    {
        $currentWeek       = $gameSave->week ?? 1;
        $controlledTeamIds = array_merge($gameSave->controlledGameTeamIds(), [(int) $gameSave->controlled_game_team_id]);

        $injuredIds = GameInjury::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $currentWeek)
            ->pluck('game_player_id')
            ->toArray();

        $suspendedIds = GameSanction::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $currentWeek)
            ->where('weeks_suspended', '>', 0)
            ->pluck('game_player_id')
            ->toArray();

        $unavailableIds = array_unique(array_merge(
            $injuredIds, $suspendedIds,
            GamePositionTraining::unavailablePlayerIds($gameSave->id, $currentWeek),
        ));

        $aiTeams = GameTeam::where('game_save_id', $gameSave->id)
            ->whereNotIn('id', $controlledTeamIds)
            ->when($teamIds !== null, fn ($query) => $query->whereIn('id', $teamIds))
            ->with(['contracts' => fn($q) => $q->activeAt($currentWeek)->with('gamePlayer')])
            ->get();

        $state = $gameSave->state ?? [];

        foreach ($aiTeams as $team) {
            // Étape 1 : rotation proactive selon philosophie
            $this->applyPhilosophyRotation($team, $unavailableIds);

            // Étape 2 : remplacement des indisponibles restants
            $this->adjustTeamLineup($team, $unavailableIds, $state);
        }

        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Réévalue les titulaires selon la philosophie de gestion.
     * Compare chaque titulaire fatigué à un remplaçant frais du même poste.
     */
    protected function applyPhilosophyRotation(GameTeam $team, array $unavailableIds, array &$state): void
    {
        $philosophy = $team->management_philosophy ?? 'balanced';
        $threshold  = self::STAMINA_THRESHOLDS[$philosophy] ?? self::STAMINA_THRESHOLDS[TeamStyle::PHILOSOPHY_BALANCED];

        $contracts = $team->contracts->filter(fn($c) => $c->gamePlayer !== null);

        // Titulaires disponibles mais fatigués
        $tiredStarters = $contracts->filter(fn($c) =>
            $c->is_starter
            && !in_array($c->gamePlayer->id, $unavailableIds)
            && ($c->gamePlayer->stamina ?? 100) < $threshold
        );

        if ($tiredStarters->isEmpty()) return;

        // Remplaçants disponibles et frais
        $freshSubs = $contracts->filter(fn($c) =>
            !$c->is_starter
            && !in_array($c->gamePlayer->id, $unavailableIds)
            && ($c->gamePlayer->stamina ?? 100) >= $threshold
        );

        if ($freshSubs->isEmpty()) return;

        foreach ($tiredStarters as $starterContract) {
            $starterPlayer   = $starterContract->gamePlayer;
            $starterPosition = $this->positionGroup($starterPlayer->position ?? '');
            $starterScore = $this->readiness($starterPlayer, $starterPosition);

            $candidate = $freshSubs
                ->filter(fn ($contract) => in_array($starterPosition, $this->playerPositionGroups($contract->gamePlayer), true))
                ->sortByDesc(fn ($contract) => $this->readiness($contract->gamePlayer, $starterPosition))
                ->first();

            if (!$candidate) continue;

            $margin = $philosophy === TeamStyle::PHILOSOPHY_STARS ? 4 : 1;
            if ($this->readiness($candidate->gamePlayer, $starterPosition) < $starterScore + $margin) continue;

            $this->swapStarter($team, $starterContract, $candidate, $state);

            // Retirer du pool
            $freshSubs = $freshSubs->reject(fn($c) => $c->id === $candidate->id);

            if ($freshSubs->isEmpty()) break;
        }
    }

    /**
     * Remplace les titulaires indisponibles (blessés/suspendus).
     */
    protected function adjustTeamLineup(GameTeam $team, array $unavailableIds, array &$state): bool
    {
        $contracts = $team->contracts->filter(fn($c) => $c->gamePlayer !== null);

        $unavailableStarters = $contracts->filter(fn($c) =>
            $c->is_starter && in_array($c->gamePlayer->id, $unavailableIds)
        );

        if ($unavailableStarters->isEmpty()) return false;

        $availableSubs = $contracts->filter(fn($c) =>
            !$c->is_starter && !in_array($c->gamePlayer->id, $unavailableIds)
        );

        if ($availableSubs->isEmpty()) return false;

        $changed = false;

        foreach ($unavailableStarters as $starterContract) {
            $starterPlayer   = $starterContract->gamePlayer;
            $starterPosition = $this->positionGroup($starterPlayer->position ?? '');

            $replacement = $availableSubs
                ->filter(fn($c) => in_array($starterPosition, $this->playerPositionGroups($c->gamePlayer), true))
                ->sortByDesc(fn ($contract) => $this->readiness($contract->gamePlayer, $starterPosition))
                ->first();

            if (!$replacement) {
                $replacement = $availableSubs
                    ->sortByDesc(fn ($contract) => $this->readiness($contract->gamePlayer, $this->positionGroup($contract->gamePlayer->position)))
                    ->first();
            }

            if (!$replacement) continue;

            $this->swapStarter($team, $starterContract, $replacement, $state);

            $availableSubs = $availableSubs->reject(fn($c) => $c->id === $replacement->id);
            $changed = true;
        }

        return $changed;
    }

    private function readiness($player, string $position): float
    {
        $stamina = (int) ($player->stamina ?? 100);
        $mastery = $this->positionGroup($player->position ?? '') === $position ? 1.0 : 0.94;

        return $this->playerOverall($player, $position) * (0.55 + 0.45 * $stamina / 100) * $mastery;
    }

    private function swapStarter(GameTeam $team, GameContract $starter, GameContract $replacement, array &$state): void
    {
        $starter->is_starter = false;
        $replacement->is_starter = true;
        $starter->save();
        $replacement->save();

        foreach ($state['lineup'][$team->id]['slots'] ?? [] as $slot => $playerId) {
            if ((int) $playerId === (int) $starter->game_player_id) {
                $state['lineup'][$team->id]['slots'][$slot] = $replacement->game_player_id;
                break;
            }
        }
    }

}

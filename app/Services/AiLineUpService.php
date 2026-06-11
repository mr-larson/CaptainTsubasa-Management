<?php

namespace App\Services;

use App\Enums\TeamStyle;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;

class AiLineUpService
{
    /**
     * Seuils de stamina par philosophie.
     * En dessous du seuil, l'IA préfère faire tourner.
     */
    private const STAMINA_THRESHOLDS = [
        TeamStyle::PHILOSOPHY_STARS      => 30,  // Pousse les stars, rotate seulement si épuisé
        TeamStyle::PHILOSOPHY_COLLECTIVE => 60,  // Rotation régulière
        TeamStyle::PHILOSOPHY_BALANCED   => 50,  // Mix raisonnable
        TeamStyle::PHILOSOPHY_ECONOMIST  => 65,  // Rotation agressive, préserve tout le monde
    ];

    /**
     * Ajuste le lineup de toutes les équipes IA avant un match.
     * 1) Réévalue les titulaires selon philosophie + stamina
     * 2) Remplace les indisponibles (blessés/suspendus)
     */
    public function adjustLineupsForWeek(GameSave $gameSave): void
    {
        $currentWeek      = $gameSave->week ?? 1;
        $controlledTeamId = $gameSave->controlled_game_team_id;

        $injuredIds = GameInjury::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $currentWeek)
            ->pluck('game_player_id')
            ->toArray();

        $suspendedIds = GameSanction::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $currentWeek)
            ->where('weeks_suspended', '>', 0)
            ->pluck('game_player_id')
            ->toArray();

        $unavailableIds = array_unique(array_merge($injuredIds, $suspendedIds));

        $aiTeams = GameTeam::where('game_save_id', $gameSave->id)
            ->when($controlledTeamId, fn($q) => $q->where('id', '!=', $controlledTeamId))
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
    protected function applyPhilosophyRotation(GameTeam $team, array $unavailableIds): void
    {
        $philosophy = $team->management_philosophy ?? 'balanced';
        $threshold  = self::STAMINA_THRESHOLDS[$philosophy] ?? 50;

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
            $starterOverall  = $this->playerOverall($starterPlayer);

            // Cherche un remplaçant frais au même poste (principal d'abord, secondaire ensuite)
            $candidate = $freshSubs
                ->filter(fn($c) => in_array($starterPosition, $this->playerPositionGroups($c->gamePlayer), true))
                ->sortBy([
                    fn($a, $b) => ($this->positionGroup($b->gamePlayer->position ?? '') === $starterPosition)
                        <=> ($this->positionGroup($a->gamePlayer->position ?? '') === $starterPosition),
                    fn($a, $b) => $this->playerOverall($b->gamePlayer) <=> $this->playerOverall($a->gamePlayer),
                ])
                ->first();

            if (!$candidate) continue;

            $candidateOverall = $this->playerOverall($candidate->gamePlayer);

            // Décision selon philosophie
            $shouldSwap = match ($philosophy) {
                // Stars : ne rotate que si le remplaçant est au moins aussi bon
                TeamStyle::PHILOSOPHY_STARS      => $candidateOverall >= $starterOverall,
                // Collectif : rotate dès que le seuil est franchi, peu importe le niveau
                TeamStyle::PHILOSOPHY_COLLECTIVE => true,
                // Économe : rotate toujours (préserve la stamina)
                TeamStyle::PHILOSOPHY_ECONOMIST  => true,
                // Équilibré : rotate si le remplaçant n'est pas trop en dessous (-10 max)
                default                          => $candidateOverall >= ($starterOverall - 10),
            };

            if (!$shouldSwap) continue;

            // Swap
            $starterContract->is_starter = false;
            $candidate->is_starter       = true;
            $starterContract->save();
            $candidate->save();

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
                ->sortBy([
                    fn($a, $b) => ($this->positionGroup($b->gamePlayer->position ?? '') === $starterPosition)
                        <=> ($this->positionGroup($a->gamePlayer->position ?? '') === $starterPosition),
                    fn($a, $b) => $this->playerOverall($b->gamePlayer) <=> $this->playerOverall($a->gamePlayer),
                ])
                ->first();

            if (!$replacement) {
                $replacement = $availableSubs
                    ->sortByDesc(fn($c) => $this->playerOverall($c->gamePlayer))
                    ->first();
            }

            if (!$replacement) continue;

            $starterContract->is_starter = false;
            $replacement->is_starter     = true;
            $starterContract->save();
            $replacement->save();

            $lineup = $state['lineup'][$team->id] ?? null;
            if ($lineup && isset($lineup['slots'])) {
                foreach ($lineup['slots'] as $slot => $playerId) {
                    if ((int) $playerId === (int) $starterPlayer->id) {
                        $state['lineup'][$team->id]['slots'][$slot] = $replacement->gamePlayer->id;
                        break;
                    }
                }
            }

            $availableSubs = $availableSubs->reject(fn($c) => $c->id === $replacement->id);
            $changed = true;
        }

        return $changed;
    }

    // ==========================
    //   HELPERS
    // ==========================

    /**
     * Groupes de poste maîtrisés par un joueur : poste principal + postes secondaires.
     */
    protected function playerPositionGroups($player): array
    {
        $groups = [$this->positionGroup($player->position ?? '')];

        foreach ((array) ($player->secondary_positions ?? []) as $secondary) {
            if (is_string($secondary) && $secondary !== '') {
                $groups[] = $this->positionGroup($secondary);
            }
        }

        return array_values(array_unique($groups));
    }

    protected function positionGroup(string $position): string
    {
        $p = strtoupper(trim($position));
        if (str_contains($p, 'GK') || str_contains($p, 'GOAL'))    return 'GK';
        if (str_contains($p, 'DEF') || str_contains($p, 'BACK'))   return 'DEF';
        if (str_contains($p, 'MDF') || str_contains($p, 'MID') || str_contains($p, 'MOF')) return 'MID';
        if (str_contains($p, 'ATT') || str_contains($p, 'FOR'))    return 'ATT';
        return 'MID';
    }

    protected function playerOverall($player): int
    {
        if (!$player) return 0;
        $stats = [
            $player->attack   ?? 0, $player->defense  ?? 0,
            $player->shot     ?? 0, $player->pass     ?? 0,
            $player->dribble  ?? 0, $player->speed    ?? 0,
            $player->tackle   ?? 0, $player->block    ?? 0,
            $player->intercept ?? 0,
        ];
        return (int) round(array_sum($stats) / max(1, count($stats)));
    }
}

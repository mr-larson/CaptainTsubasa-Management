<?php

namespace App\Services;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Collection;

class AILineupService
{
    /**
     * Ajuste le lineup de toutes les équipes IA avant un match.
     * Remplace les titulaires indisponibles (blessés/suspendus) par
     * des remplaçants disponibles au même poste.
     */
    public function adjustLineupsForWeek(GameSave $gameSave): void
    {
        $currentWeek      = $gameSave->week ?? 1;
        $controlledTeamId = $gameSave->controlled_game_team_id;

        // Joueurs blessés et suspendus cette semaine
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

        if (empty($unavailableIds)) return;

        // Toutes les équipes IA
        $aiTeams = GameTeam::where('game_save_id', $gameSave->id)
            ->when($controlledTeamId, fn($q) => $q->where('id', '!=', $controlledTeamId))
            ->with(['contracts.gamePlayer'])
            ->get();

        $state = $gameSave->state ?? [];

        foreach ($aiTeams as $team) {
            $changed = $this->adjustTeamLineup($team, $unavailableIds, $state);
            if ($changed) {
                // Sauvegarder le lineup ajusté dans state
            }
        }

        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Ajuste le lineup d'une équipe en remplaçant les indisponibles.
     * Retourne true si des changements ont été effectués.
     */
    protected function adjustTeamLineup(GameTeam $team, array $unavailableIds, array &$state): bool
    {
        $contracts = $team->contracts->filter(fn($c) => $c->gamePlayer !== null);

        // Titulaires indisponibles
        $unavailableStarters = $contracts->filter(fn($c) =>
            $c->is_starter && in_array($c->gamePlayer->id, $unavailableIds)
        );

        if ($unavailableStarters->isEmpty()) return false;

        // Remplaçants disponibles
        $availableSubs = $contracts->filter(fn($c) =>
            !$c->is_starter && !in_array($c->gamePlayer->id, $unavailableIds)
        );

        if ($availableSubs->isEmpty()) return false;

        $changed = false;

        foreach ($unavailableStarters as $starterContract) {
            $starterPlayer   = $starterContract->gamePlayer;
            $starterPosition = $this->positionGroup($starterPlayer->position ?? '');

            // Cherche un remplaçant au même poste
            $replacement = $availableSubs->first(fn($c) =>
                $this->positionGroup($c->gamePlayer->position ?? '') === $starterPosition
            );

            // Si pas de remplaçant au même poste, prend n'importe quel remplaçant disponible
            if (!$replacement) {
                $replacement = $availableSubs->first();
            }

            if (!$replacement) continue;

            // Swap is_starter en DB
            $starterContract->is_starter     = false;
            $replacement->is_starter         = true;
            $starterContract->save();
            $replacement->save();

            // Mettre à jour le lineup dans state si défini
            $lineup = $state['lineup'][$team->id] ?? null;
            if ($lineup && isset($lineup['slots'])) {
                foreach ($lineup['slots'] as $slot => $playerId) {
                    if ((int) $playerId === (int) $starterPlayer->id) {
                        $state['lineup'][$team->id]['slots'][$slot] = $replacement->gamePlayer->id;
                        break;
                    }
                }
            }

            // Retirer ce remplaçant du pool
            $availableSubs = $availableSubs->reject(fn($c) => $c->id === $replacement->id);
            $changed = true;
        }

        return $changed;
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
}

<?php

namespace App\Services;

use App\Models\GameSave;
use App\Models\GameTeam;
use App\Models\GameContract;
use Illuminate\Support\Facades\DB;

class AITrainingService
{
    public function trainForWeek(GameSave $gameSave): void
    {
        $state = $gameSave->state ?? [];

        // Empêcher un double entraînement IA pour la même semaine
        $aiWeekKey = 'ai_training_week';
        if (($state[$aiWeekKey] ?? null) === $gameSave->week) {
            return; // déjà entraîné pour cette semaine
        }

        // Équipe contrôlée par le joueur
        $controlledTeamId = $gameSave->controlled_game_team_id;

        // Toutes les équipes de la save, sauf l'équipe contrôlée
        $aiTeams = GameTeam::where('game_save_id', $gameSave->id)
            ->when($controlledTeamId, fn($q) => $q->where('id', '!=', $controlledTeamId))
            ->with(['contracts.gamePlayer'])
            ->get();

        foreach ($aiTeams as $team) {
            $this->trainTeam($team);
        }

        // Marquer cette semaine comme traitée
        $state[$aiWeekKey] = $gameSave->week;
        $gameSave->state = $state;
        $gameSave->save();
    }

    protected function trainTeam(GameTeam $team): void
    {
        // Contrats + joueurs liés
        $contracts = $team->contracts->filter(fn($c) => $c->gamePlayer !== null);

        if ($contracts->isEmpty()) {
            return;
        }

        $minStamina = config('training.min_stamina_to_train', 10);
        $staminaCost = config('training.stamina_cost', 5);
        $statMin = config('training.stat_min', 0);
        $statMax = config('training.stat_max', 100);

        // IA un peu moins forte sur les gains
        $gainMin = 1;
        $gainMax = 3;

        // Nombre d'entraînements pour cette équipe cette semaine
        $trainCount = rand(1, 3);

        // Priorité aux titulaires
        $starterContracts = $contracts->where('is_starter', true);
        $subContracts     = $contracts->where('is_starter', false);

        for ($i = 0; $i < $trainCount; $i++) {

            // On privilégie toujours les titulaires si possible
            $pool = $starterContracts->filter(fn($c) => $c->gamePlayer->stamina >= $minStamina);

            if ($pool->isEmpty()) {
                $pool = $subContracts->filter(fn($c) => $c->gamePlayer->stamina >= $minStamina);
            }

            if ($pool->isEmpty()) {
                // Personne à entraîner : tous trop fatigués
                break;
            }

            // On choisit le joueur le plus "faible" selon une faiblesse globale
            $contract = $pool->sortBy(fn($c) => $this->globalWeakness($c->gamePlayer))->first();
            $player   = $contract->gamePlayer;

            $statKey = $this->lowestRelevantStat($player);

            if (!$statKey) {
                continue;
            }

            DB::transaction(function () use ($player, $statKey, $gainMin, $gainMax, $staminaCost, $statMin, $statMax) {
                $gain = rand($gainMin, $gainMax);

                $oldStat = (int) ($player->{$statKey} ?? 0);
                $newStat = min($statMax, max($statMin, $oldStat + $gain));

                $oldStamina = (int) $player->stamina;
                $newStamina = max($statMin, $oldStamina - $staminaCost);

                $player->{$statKey} = $newStat;
                $player->stamina     = $newStamina;
                $player->save();
            });
        }
    }

    protected function globalWeakness($player): float
    {
        // Simple moyenne des stats clés pour déterminer la "faiblesse"
        $values = [
            (int) $player->attack,
            (int) $player->defense,
            (int) $player->speed,
            (int) $player->stamina,
        ];

        return array_sum($values) / max(count($values), 1);
    }

    protected function lowestRelevantStat($player): ?string
    {
        // Stats pertinentes par défaut (joueurs de champ)
        $fields = [
            'speed',
            'attack',
            'defense',
            'shot',
            'pass',
            'dribble',
            'tackle',
            'intercept',
            'block',
        ];

        // Si gardien, on inclut les stats GK
        if (strtoupper($player->position) === 'GK') {
            $fields[] = 'hand_save';
            $fields[] = 'punch_save';
        }

        $stats = [];
        foreach ($fields as $f) {
            $stats[$f] = (int) ($player->{$f} ?? 0);
        }

        asort($stats); // tri croissant

        return array_key_first($stats) ?: null;
    }
}

<?php

namespace App\Services\Training;

use App\Contracts\Randomizer;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Entraînement individuel de l'équipe contrôlée : chaque séance fait gagner un nombre aléatoire de points sur une stat, coûte de la stamina et de l'argent, dans la limite d'un quota hebdomadaire. */
class TrainingService
{

    /** Injecte le générateur aléatoire (gain de chaque séance). */
    public function __construct(private Randomizer $random)
    {
    }
    /**
     * Applique les entraînements pour une sauvegarde.
     *
     * @param  GameSave  $gameSave
     * @param  int       $season
     * @param  int       $week
     * @param  array     $trainings  ex: [ ['player_id' => 1, 'stat' => 'shot'], ... ]
     * @return array     Résumé des entraînements appliqués
     * @throws ValidationException
     */
    public function applyTrainings(GameSave $gameSave, int $season, int $week, array $trainings): array
    {
        $maxPerWeek   = (int) $gameSave->getConfig('training_max_per_week', config('training.max_trainings_per_week', 3));
        $minStamina   = (int) $gameSave->getConfig('training_min_stamina', config('training.min_stamina_to_train', 10));
        $staminaCost  = (int) $gameSave->getConfig('training_stamina_cost', config('training.stamina_cost', 5));
        $gainMin      = (int) $gameSave->getConfig('training_gain_min', config('training.gain_min', 1));
        $gainMax      = (int) $gameSave->getConfig('training_gain_max', config('training.gain_max', 5));
        // Config enregistrée avant le contrôle min ≤ max : on remet dans l'ordre.
        [$gainMin, $gainMax] = [min($gainMin, $gainMax), max($gainMin, $gainMax)];
        $trainingCost = (int) $gameSave->getConfig('training_cost', config('training.cost', 0));
        $statMin      = config('training.stat_min', 0);
        $statMax      = config('training.stat_max', 100);

        // 1️⃣ Lire l'état actuel des entraînements dans state. Le quota est
        // compté par équipe : en hot-seat, chaque manager a ses propres séances.
        $state    = $gameSave->gameState();
        $teamId   = (int) $gameSave->controlled_game_team_id;

        // Séances de la semaine courante, toutes équipes confondues.
        $weekEntries = $state->weekTrainingEntries($season, $week);

        // Entrées sans team_id (antérieures au quota par équipe) : comptées pour tous.
        $currentEntries = array_values(array_filter(
            $weekEntries,
            fn ($e) => !isset($e['team_id']) || (int) $e['team_id'] === $teamId
        ));

        if (count($currentEntries) >= $maxPerWeek) {
            throw ValidationException::withMessages([
                'trainings' => "Tu as déjà utilisé tous tes entraînements cette semaine.",
            ]);
        }

        if (count($currentEntries) + count($trainings) > $maxPerWeek) {
            throw ValidationException::withMessages([
                'trainings' => "Tu dépasses la limite de {$maxPerWeek} entraînements pour cette semaine.",
            ]);
        }

        // 2️⃣ Joueurs distincts dans la requête
        $playerIds = array_column($trainings, 'player_id');
        if (array_intersect($playerIds, GamePositionTraining::unavailablePlayerIds($gameSave->id, $week))) {
            throw ValidationException::withMessages(['trainings' => 'Un joueur en formation ne peut pas suivre une autre séance.']);
        }
        if (count($playerIds) !== count(array_unique($playerIds))) {
            throw ValidationException::withMessages([
                'trainings' => 'Chaque joueur ne peut être entraîné qu\'une fois par semaine.',
            ]);
        }

        // 3️⃣ Récupérer l’équipe contrôlée
        /** @var GameTeam|null $controlledTeam */
        $controlledTeam = $gameSave->controlledGameTeam;

        if (!$controlledTeam) {
            throw ValidationException::withMessages([
                'trainings' => 'Aucune équipe contrôlée définie pour cette sauvegarde.',
            ]);
        }

        // 4️⃣ Charger les joueurs ciblés pour cette sauvegarde
        $players = GamePlayer::query()
            ->where('game_save_id', $gameSave->id)
            ->whereIn('id', $playerIds)
            ->get()
            ->keyBy('id');

        if ($players->count() !== count($playerIds)) {
            throw ValidationException::withMessages([
                'trainings' => 'Un ou plusieurs joueurs sélectionnés sont introuvables dans cette sauvegarde.',
            ]);
        }

        // Vérifier qu'ils sont sous contrat avec l'équipe contrôlée
        $invalid = [];

        foreach ($players as $playerId => $player) {
            $hasContractWithControlledTeam = $player->contracts()
                ->where('game_save_id', $gameSave->id)
                ->where('game_team_id', $controlledTeam->id)
                ->exists();

            if (!$hasContractWithControlledTeam) {
                $invalid[] = $playerId;
            }
        }

        if (!empty($invalid)) {
            throw ValidationException::withMessages([
                'trainings' => 'Certains joueurs sélectionnés ne font pas partie de ton équipe.',
            ]);
        }

        // 5️⃣ Vérifier qu'ils n'ont pas déjà été entraînés cette semaine
        $alreadyTrainedIds = array_column($currentEntries, 'player_id');
        $overlap = array_intersect($alreadyTrainedIds, $playerIds);

        if (!empty($overlap)) {
            throw ValidationException::withMessages([
                'trainings' => 'Certains joueurs ont déjà été entraînés cette semaine.',
            ]);
        }

        // 5️⃣bis Vérifier le budget : chaque entraînement coûte de l'argent.
        $totalCost = $trainingCost * count($trainings);

        if ($totalCost > (int) ($controlledTeam->budget ?? 0)) {
            throw ValidationException::withMessages([
                'trainings' => "Budget insuffisant : {$totalCost} GL nécessaires pour {$this->plural(count($trainings), 'entraînement')}.",
            ]);
        }

        // 6️⃣ Appliquer les entraînements en transaction
        $applied = [];

        DB::transaction(function () use (
            $players,
            $trainings,
            $minStamina,
            $staminaCost,
            $gainMin,
            $gainMax,
            $statMin,
            $statMax,
            $trainingCost,
            $totalCost,
            $controlledTeam,
            $weekEntries,
            $state,
            &$applied,
            $season,
            $week
        ) {
            foreach ($trainings as $entry) {
                $playerId = (int) $entry['player_id'];
                $stat     = $entry['stat'];

                /** @var GamePlayer $player */
                $player = $players[$playerId];

                // Stamina minimale
                if ($player->stamina < $minStamina) {
                    throw ValidationException::withMessages([
                        'trainings' => "Le joueur {$player->full_name} n'a pas assez d'endurance pour s'entraîner.",
                    ]);
                }

                // Gain aléatoire
                $gain = $this->random->int($gainMin, $gainMax);

                // Appliquer le gain de stat avec clamp
                $oldStatValue = (int) ($player->{$stat} ?? 0);
                $newStatValue = min($statMax, max($statMin, $oldStatValue + $gain));

                $oldStamina = (int) $player->stamina;
                $newStamina = max($statMin, $oldStamina - $staminaCost);

                $player->{$stat} = $newStatValue;
                $player->stamina = $newStamina;
                $player->save();

                $applied[] = [
                    'player_id'    => $player->id,
                    'stat'         => $stat,
                    'old_stat'     => $oldStatValue,
                    'new_stat'     => $newStatValue,
                    'gain'         => $newStatValue - $oldStatValue,
                    'old_stamina'  => $oldStamina,
                    'new_stamina'  => $newStamina,
                    'stamina_cost' => $oldStamina - $newStamina,
                    'cost'         => $trainingCost,
                ];
            }

            // Débiter le budget de l'équipe contrôlée du coût total.
            if ($totalCost > 0) {
                $controlledTeam->budget = max(0, (int) ($controlledTeam->budget ?? 0) - $totalCost);
                $controlledTeam->save();
            }

            if ($state->trainingHistory() === null) {
                $previous = $state->training() ?? [];
                $state->putTrainingHistory(array_map(fn ($entry) => $entry + [
                    'season' => $previous['season'] ?? $season, 'week' => $previous['week'] ?? $week,
                ], $previous['entries'] ?? []));
            }
            $history = array_values(array_filter($state->trainingHistory() ?? [],
                fn ($entry) => (int) ($entry['season'] ?? 0) === $season));
            foreach ($applied as $entry) {
                $history[] = $entry + ['season' => $season, 'week' => $week];
            }
            $state->putTrainingHistory(array_slice($history, -200));

            // Séances de la semaine : les précédentes plus celles-ci.
            $state->setWeekTraining($season, $week, array_merge(
                $weekEntries,
                array_map(function ($e) use ($controlledTeam) {
                    return [
                        'team_id'      => $controlledTeam->id,
                        'player_id'    => $e['player_id'],
                        'stat'         => $e['stat'],
                        'gain'         => $e['gain'],
                        'stamina_cost' => $e['stamina_cost'],
                        'cost'         => $e['cost'],
                        'created_at'   => now()->toIso8601String(),
                    ];
                }, $applied)
            ));
        });

        $gameSave->save();

        return $applied;
    }

    /** Petit utilitaire d'accord en nombre pour les messages d'erreur. */
    private function plural(int $count, string $word): string
    {
        return $count > 1 ? "{$count} {$word}s" : "{$count} {$word}";
    }
}

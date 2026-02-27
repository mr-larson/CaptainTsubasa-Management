<?php

namespace App\Services;

use App\Models\GameSave;
use App\Models\GamePlayer;
use App\Models\GameTeam;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainingService
{
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
        $maxPerWeek   = config('training.max_trainings_per_week', 3);
        $minStamina   = config('training.min_stamina_to_train', 10);
        $staminaCost  = config('training.stamina_cost', 5);
        $gainMin      = config('training.gain_min', 1);
        $gainMax      = config('training.gain_max', 5);
        $statMin      = config('training.stat_min', 0);
        $statMax      = config('training.stat_max', 100);

        // 1️⃣ Lire l'état actuel des entraînements dans state
        $state    = $gameSave->state ?? [];
        $training = $state['training'] ?? null;

        $currentEntries = [];

        if (
            $training &&
            isset($training['season'], $training['week']) &&
            (int) $training['season'] === $season &&
            (int) $training['week'] === $week
        ) {
            $currentEntries = $training['entries'] ?? [];
        }

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
            &$state,
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
                $gain = random_int($gainMin, $gainMax);

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
                ];
            }

            // Mise à jour de state['training']
            $state['training'] = [
                'season'  => $season,
                'week'    => $week,
                'entries' => array_merge(
                    $state['training']['entries'] ?? [],
                    array_map(function ($e) {
                        return [
                            'player_id'    => $e['player_id'],
                            'stat'         => $e['stat'],
                            'gain'         => $e['gain'],
                            'stamina_cost' => $e['stamina_cost'],
                            'created_at'   => now()->toIso8601String(),
                        ];
                    }, $applied)
                ),
            ];
        });

        $gameSave->state = $state;
        $gameSave->save();

        return $applied;
    }
}

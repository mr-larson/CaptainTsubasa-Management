<?php

namespace App\Services\Training;

use App\Enums\PlayerPosition;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Formation d'un joueur à un nouveau poste ou à une technique spéciale.
 *
 * Durée d'indisponibilité (en semaines, donc en matchs) :
 *  - joueur de champ, ligne voisine (DEF ↔ MIL, MIL ↔ ATT)   : 1
 *  - joueur de champ, deux lignes d'écart (DEF ↔ ATT)        : 2
 *  - joueur de champ → gardien                               : 3
 *  - gardien → défenseur                                     : 2
 *  - gardien → milieu ou attaquant                           : 3
 * La distance part du poste connu (principal ou secondaire) le plus proche.
 *
 * Une formation compte comme un entraînement de la semaine (quota et coût).
 */
class PositionTrainingService
{
    /** Numéro de ligne de chaque poste de champ (défense 1, milieu 2, attaque 3), pour mesurer l'écart entre deux postes. */
    private const LINE = [
        'Defender'   => 1,
        'Midfielder' => 2,
        'Forward'    => 3,
    ];

    /** Durée d'indisponibilité en semaines pour apprendre le poste $target, depuis les postes déjà connus : 3 pour devenir gardien ; sinon l'écart de lignes avec le poste connu le plus proche ; pour un gardien qui devient joueur de champ, 2 vers défenseur et 3 ailleurs. */
    public static function durationFor(array $knownPositions, string $target): int
    {
        $gk = PlayerPosition::Goalkeeper->value;

        if ($target === $gk) {
            return 3;
        }

        $fieldLines = array_values(array_filter(array_map(fn ($p) => self::LINE[$p] ?? null, $knownPositions)));
        if (!$fieldLines) {
            // Gardien de formation : défenseur (ligne la plus proche) ou au-delà.
            return $target === PlayerPosition::Defender->value ? 2 : 3;
        }

        $targetLine = self::LINE[$target];

        return min(array_map(fn ($line) => abs($line - $targetLine), $fieldLines));
    }

    /**
     * Lance la formation du joueur au poste $target pour l'équipe contrôlée.
     *
     * @throws ValidationException
     */
    public function start(GameSave $gameSave, int $playerId, string $target, ?array $move = null, ?GameTeam $team = null): GamePositionTraining
    {
        $season  = (int) ($gameSave->season ?? 1);
        $week    = (int) ($gameSave->week ?? 1);
        $team ??= $gameSave->controlledGameTeam;
        $teamId = (int) ($team?->id ?? 0);

        $errorKey = $move ? 'special_training' : 'position_training';
        $fail = fn (string $message) => throw ValidationException::withMessages([$errorKey => $message]);

        if (!$team || (int) $team->game_save_id !== (int) $gameSave->id) {
            $fail('Aucune équipe contrôlée définie pour cette sauvegarde.');
        }

        /** @var GamePlayer|null $player */
        $player = GamePlayer::where('game_save_id', $gameSave->id)->find($playerId);
        $underContract = $player && $player->contracts()
            ->where('game_save_id', $gameSave->id)
            ->where('game_team_id', $teamId)
            ->activeAt($week)
            ->exists();
        if (!$underContract) {
            $fail('Ce joueur ne fait pas partie de ton équipe.');
        }

        $known = array_values(array_unique(array_filter(array_merge(
            [$player->position],
            (array) ($player->secondary_positions ?? []),
        ))));
        if ($move) {
            $action = TrainableSpecialMoves::actionOf($move);
            $keeperMove = in_array($action, ['hand_save', 'punch_save'], true);
            if ($keeperMove !== ($player->position === PlayerPosition::Goalkeeper->value)) {
                $fail('Cette technique est incompatible avec le poste principal du joueur.');
            }
            foreach ($player->special_moves ?? [] as $knownMove) {
                if (TrainableSpecialMoves::actionOf($knownMove) === $action) {
                    $fail('Ce joueur maîtrise déjà une technique spéciale pour cette action.');
                }
            }
        } elseif (in_array($target, $known, true)) {
            $fail("{$player->lastname} sait déjà jouer à ce poste.");
        }

        $inProgress = GamePositionTraining::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->whereNull('completed_at')
            ->exists();
        if ($inProgress) {
            $fail("{$player->lastname} est déjà en formation.");
        }

        $injured = GameInjury::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->where('week_return', '>', $week)
            ->exists();
        $suspended = GameSanction::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->where('week_return', '>', $week)
            ->where('weeks_suspended', '>', 0)
            ->exists();
        if ($injured || $suspended) {
            $fail("{$player->lastname} est indisponible : il pourra se former à son retour.");
        }

        // Quota et coût partagés avec les séances d'entraînement de la semaine.
        $maxPerWeek = (int) $gameSave->getConfig('training_max_per_week', config('training.max_trainings_per_week', 3));
        $cost       = (int) $gameSave->getConfig($move ? 'special_training_cost' : 'position_training_cost');

        $weekEntries = $gameSave->gameState()->weekTrainingEntries($season, $week);
        $teamEntries = array_filter($weekEntries, fn ($e) => !isset($e['team_id']) || (int) $e['team_id'] === $teamId);

        if (count($teamEntries) >= $maxPerWeek) {
            $fail('Tu as déjà utilisé tous tes entraînements cette semaine.');
        }
        if (in_array($player->id, array_map('intval', array_column($teamEntries, 'player_id')), true)) {
            $fail("{$player->lastname} a déjà été entraîné cette semaine.");
        }
        if ($cost > (int) ($team->budget ?? 0)) {
            $fail("Budget insuffisant : {$cost} GL nécessaires.");
        }

        $duration = $move ? 1 : self::durationFor($known, $target);

        return DB::transaction(function () use ($gameSave, $team, $player, $target, $week, $season, $duration, $cost, $weekEntries, $move) {
            if ($cost > 0) {
                $team->budget = max(0, (int) $team->budget - $cost);
                $team->save();
            }

            $gameSave->gameState()->setWeekTraining($season, $week, array_merge($weekEntries, [[
                'team_id'      => $team->id,
                'player_id'    => $player->id,
                'stat'         => $move ? 'special_move' : 'position',
                ...($move ? ['special_label' => $move['label']] : ['position' => $target]),
                'gain'         => 0,
                'stamina_cost' => 0,
                'cost'         => $cost,
                'created_at'   => now()->toIso8601String(),
            ]]));
            $gameSave->save();

            return GamePositionTraining::create([
                'game_save_id'    => $gameSave->id,
                'game_player_id'  => $player->id,
                'game_team_id'    => $team->id,
                'from_position'   => $player->position,
                'target_position' => $move ? null : $target,
                'special_move'     => $move,
                'cost'             => $cost,
                'week_start'      => $week,
                'week_return'     => $week + $duration,
            ]);
        });
    }

    /**
     * Formations arrivées à terme pour la semaine $week (ou toutes, $all) :
     * le poste rejoint les postes secondaires du joueur.
     */
    public function completeDue(GameSave $gameSave, int $week, bool $all = false): void
    {
        GamePositionTraining::where('game_save_id', $gameSave->id)
            ->whereNull('completed_at')
            ->when(!$all, fn ($q) => $q->where('week_return', '<=', $week))
            ->with('gamePlayer')
            ->get()
            ->each(function (GamePositionTraining $training) {
                $player = $training->gamePlayer;
                if ($player && $training->special_move) {
                    $moves = $player->special_moves ?? [];
                    $move = $training->special_move;
                    $action = TrainableSpecialMoves::actionOf($move);
                    if (!in_array($action, array_map(TrainableSpecialMoves::actionOf(...), $moves), true)) {
                        $player->special_moves = [...$moves, $move];
                        $player->save();
                    }
                } elseif ($player) {
                    $secondary = (array) ($player->secondary_positions ?? []);
                    if ($training->target_position !== $player->position && !in_array($training->target_position, $secondary, true)) {
                        $secondary[] = $training->target_position;
                        $player->secondary_positions = array_values($secondary);
                        $player->save();
                    }
                }
                $training->completed_at = now();
                $training->save();
            });
    }
}

<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Http\Requests\Training\StoreTrainingRequest;
use App\Models\GameSaves\GameSave;
use App\Enums\PlayerPosition;
use App\Services\PositionTrainingService;
use App\Services\TrainingService;
use App\Services\TrainableSpecialMoves;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class TrainingController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /**
     * Lance des entraînements pour une sauvegarde donnée.
     */
    public function store(
        StoreTrainingRequest $request,
        GameSave $gameSave,
        TrainingService $trainingService
    ): RedirectResponse {
        $this->authorizeGameSave('update', $gameSave);

        $data = $request->validated();

        // Verrou par save : deux soumissions simultanées ne contournent pas la
        // limite d'entraînements par semaine (state['training'] relu sous verrou).
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($data, $trainingService) {
            // Le quota est indexé sur la saison/semaine : elles viennent de la
            // save, jamais du client (sinon week=1000, 1001… remet le quota à zéro).
            // Une page restée sur une semaine passée est refusée.
            $season = (int) ($gameSave->season ?? 1);
            $week   = (int) ($gameSave->week ?? 1);
            if ((int) $data['season'] !== $season || (int) $data['week'] !== $week) {
                throw ValidationException::withMessages([
                    'trainings' => 'La semaine a changé : recharge la page avant de t\'entraîner.',
                ]);
            }

            $trainingService->applyTrainings($gameSave, $season, $week, $data['trainings']);

            return back()->with('success', 'Entraînement effectué avec succès.');
        });
    }

    public function storeSpecial(Request $request, GameSave $gameSave, PositionTrainingService $service): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);
        $data = $request->validate([
            'player_id' => ['required', 'integer', 'min:1'],
            'move_key' => ['required', 'string', 'max:255'],
            'season' => ['required', 'integer'],
            'week' => ['required', 'integer'],
        ]);

        return $this->withSaveLock($gameSave, function (GameSave $save) use ($data, $service) {
            if (!$save->isInMatchPhase() || (int) $data['season'] !== (int) $save->season || (int) $data['week'] !== (int) $save->week) {
                throw ValidationException::withMessages(['special_training' => 'La semaine ou la phase a changé. Recharge la page.']);
            }

            $move = collect(TrainableSpecialMoves::catalog())->firstWhere('key', $data['move_key']);
            if (!$move) {
                throw ValidationException::withMessages(['special_training' => 'Cette technique ne peut pas être apprise.']);
            }

            $training = $service->start($save, (int) $data['player_id'], $move['key'], $move);

            return back()->with('success', "Formation « {$move['label']} » lancée. Retour en semaine {$training->week_return}.");
        });
    }

    /**
     * Envoie un joueur en formation à un nouveau poste (indisponible quelques matchs).
     */
    public function storePosition(
        Request $request,
        GameSave $gameSave,
        PositionTrainingService $service
    ): RedirectResponse {
        $this->authorizeGameSave('update', $gameSave);

        $data = $request->validate([
            'player_id' => ['required', 'integer'],
            'position'  => ['required', new Enum(PlayerPosition::class)],
        ]);

        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($data, $service) {
            $training = $service->start($gameSave, (int) $data['player_id'], $data['position']);
            $weeks    = $training->week_return - $training->week_start;
            $label    = PlayerPosition::labels()[$training->target_position] ?? $training->target_position;

            return back()->with('success', "Formation au poste de {$label} lancée : absent {$weeks} match" . ($weeks > 1 ? 's' : '') . " (retour semaine {$training->week_return}).");
        });
    }
}

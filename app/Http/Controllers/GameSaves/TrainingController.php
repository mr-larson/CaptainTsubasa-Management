<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Http\Requests\Training\StoreTrainingRequest;
use App\Models\GameSaves\GameSave;
use App\Services\TrainingService;
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
}

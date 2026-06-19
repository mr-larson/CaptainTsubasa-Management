<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Requests\Training\StoreTrainingRequest;
use App\Models\GameSaves\GameSave;
use App\Services\TrainingService;
use Illuminate\Http\RedirectResponse;

class TrainingController extends Controller
{
    use AuthorizesGameSave;

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

        $trainingService->applyTrainings(
            $gameSave,
            (int) $data['season'],
            (int) $data['week'],
            $data['trainings']
        );

        return back()->with('success', 'Entraînement effectué avec succès.');
    }
}

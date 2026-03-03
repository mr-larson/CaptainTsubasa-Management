<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Requests\Training\StoreTrainingRequest;
use App\Models\GameSaves\GameSave;
use App\Services\TrainingService;
use Illuminate\Http\RedirectResponse;

class TrainingController extends Controller
{
    /**
     * Lance des entraînements pour une sauvegarde donnée.
     */
    public function store(
        StoreTrainingRequest $request,
        GameSave $gameSave,
        TrainingService $trainingService
    ): RedirectResponse {
        // Vérifier que la sauvegarde appartient bien au joueur connecté
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }

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

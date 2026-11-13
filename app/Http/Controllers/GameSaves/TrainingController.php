<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Requests\GameSaves\StartPositionTrainingRequest;
use App\Http\Requests\GameSaves\StoreSpecialTrainingRequest;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Http\Requests\Training\StoreTrainingRequest;
use App\Models\GameSaves\GameSave;
use App\Enums\PlayerPosition;
use App\Services\PositionTrainingService;
use App\Services\TrainingService;
use App\Services\TrainableSpecialMoves;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/** Entraînements d'une sauvegarde : séances de la semaine, formation à un nouveau poste et apprentissage de techniques spéciales. Les actions d'écriture exigent le droit de modifier la sauvegarde et le verrou de sauvegarde. */
class TrainingController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /**
     * Séances d'entraînement IA d'une semaine de la saison en cours (onglet
     * Entraînement) : l'historique complet reste côté serveur.
     */
    public function aiWeek(GameSave $gameSave, int $week): \Illuminate\Http\JsonResponse
    {
        $this->authorizeGameSave('view', $gameSave);

        $season = (int) ($gameSave->season ?? 1);

        return response()->json([
            'entries' => $gameSave->gameState()->aiTrainingHistoryOfWeek($season, $week),
        ]);
    }

    /** Lance les entraînements de la semaine pour l'équipe contrôlée. Refuse (erreur de validation) si la saison ou la semaine envoyée n'est plus celle de la sauvegarde, puis applique les séances via TrainingService, sous verrou pour respecter le quota hebdomadaire. Revient en arrière avec un message de succès. */
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

    /** Lance l'apprentissage d'une technique spéciale par un joueur. Refuse hors phase de match, si la semaine a changé ou si la technique n'est pas dans le catalogue apprenable. Revient en arrière en indiquant la semaine de retour du joueur. */
    public function storeSpecial(StoreSpecialTrainingRequest $request, GameSave $gameSave, PositionTrainingService $service): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);
        $data = $request->validated();

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
        StartPositionTrainingRequest $request,
        GameSave $gameSave,
        PositionTrainingService $service
    ): RedirectResponse {
        $this->authorizeGameSave('update', $gameSave);

        $data = $request->validated();

        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($data, $service) {
            $training = $service->start($gameSave, (int) $data['player_id'], $data['position']);
            $weeks    = $training->week_return - $training->week_start;
            $label    = PlayerPosition::labels()[$training->target_position] ?? $training->target_position;

            return back()->with('success', "Formation au poste de {$label} lancée : absent {$weeks} match" . ($weeks > 1 ? 's' : '') . " (retour semaine {$training->week_return}).");
        });
    }
}

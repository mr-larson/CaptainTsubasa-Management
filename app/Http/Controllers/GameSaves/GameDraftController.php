<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\DraftAIService;
use App\Services\DraftService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameDraftController extends Controller
{
    use AuthorizesGameSave;

    /**
     * Écran de draft (initial ou intersaison).
     */
    public function show(Request $request, GameSave $gameSave): Response
    {
        $this->authorizeGameSave('view', $gameSave);

        if (!in_array($gameSave->phase, ['draft', 'intersaison_draft'], true)) {
            return redirect()->route('game-saves.Play', $gameSave);
        }

        $gameTeams = GameTeam::with(['contracts.gamePlayer'])
            ->where('game_save_id', $gameSave->id)
            ->orderBy('name')
            ->get();

        $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts')
            ->orderBy('lastname')
            ->get();

        return Inertia::render('GameSaves/Draft', [
            'gameSave'         => $gameSave,
            'teams'            => $gameTeams,
            'freePlayers'      => $freePlayers,
            'draftState'       => $gameSave->state['draft'] ?? null,
            'controlledTeamId' => $gameSave->controlled_game_team_id,
        ]);
    }

    /**
     * Exécute un pick IA (appelé par le frontend pour chaque tour IA).
     */
    public function aiPick(Request $request, GameSave $gameSave)
    {
        $this->authorizeGameSave('update', $gameSave);

        if (!in_array($gameSave->phase, ['draft', 'intersaison_draft'], true)) {
            return response()->json(['error' => 'Not in draft phase'], 400);
        }

        $draftService = app(DraftService::class);

        // Vérifier que ce n'est PAS le tour du joueur humain
        if ($draftService->isHumanTurn($gameSave)) {
            return response()->json(['error' => 'It is your turn to pick'], 400);
        }

        $draft = ($gameSave->state ?? [])['draft'] ?? null;
        if (!$draft || ($draft['completed'] ?? false)) {
            // Draft terminé → finaliser
            $draftService->finalizeDraft($gameSave);
            return response()->json(['completed' => true]);
        }

        // L'IA choisit un joueur
        $aiService     = app(DraftAIService::class);
        $currentTeamId = $draftService->getCurrentTeamId($gameSave);
        $team          = GameTeam::find($currentTeamId);

        if (!$team) {
            return response()->json(['error' => 'Team not found'], 400);
        }

        // Vérifier si l'IA veut arrêter
        $squadSize = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $team->id)
            ->count();

        if ($aiService->shouldFinishDraft($team, $squadSize, $team->budget ?? 0)) {
            $draftService->finishTeamDraft($gameSave, $team->id);
            $gameSave->refresh();
            $draftState = $gameSave->state['draft'] ?? [];

            return response()->json([
                'pick'       => null,
                'finished'   => true,
                'team_name'  => $team->name,
                'draftState' => $draftState,
                'completed'  => $draftState['completed'] ?? false,
            ]);
        }

        $playerId = $aiService->chooseBestPlayer($gameSave, $team);

        if (!$playerId) {
            // Aucun joueur trouvable → cette équipe a fini (budget insuffisant)
            $draftService->finishTeamDraft($gameSave, $team->id);
            $gameSave->refresh();
            $draftState = $gameSave->state['draft'] ?? [];

            return response()->json([
                'pick'       => null,
                'finished'   => true,
                'team_name'  => $team->name,
                'draftState' => $draftState,
                'completed'  => $draftState['completed'] ?? false,
            ]);
        }

        $pick = $draftService->executePick($gameSave, $playerId);
        $gameSave->refresh();

        $draftState = $gameSave->state['draft'] ?? [];

        if ($draftState['completed'] ?? false) {
            $draftService->finalizeDraft($gameSave);
        }

        return response()->json([
            'pick'       => $pick,
            'draftState' => $draftState,
            'completed'  => $draftState['completed'] ?? false,
        ]);
    }

    /**
     * Le joueur humain pioche un joueur.
     */
    public function pick(Request $request, GameSave $gameSave)
    {
        $this->authorizeGameSave('update', $gameSave);

        if (!in_array($gameSave->phase, ['draft', 'intersaison_draft'], true)) {
            return response()->json(['error' => 'Not in draft phase'], 400);
        }

        $data = $request->validate([
            'player_id' => ['required', 'integer'],
        ]);

        $draftService = app(DraftService::class);

        if (!$draftService->isHumanTurn($gameSave)) {
            return response()->json(['error' => 'Not your turn'], 400);
        }

        // Message explicite si le joueur refuse l'équipe contrôlée (rancune coach)
        $pickedPlayer  = \App\Models\GameSaves\GamePlayer::where('game_save_id', $gameSave->id)->find($data['player_id']);
        $currentTeamId = $draftService->getCurrentTeamId($gameSave);
        if ($pickedPlayer && $currentTeamId && $draftService->playerRefusesTeam($gameSave, $pickedPlayer, (int) $currentTeamId)) {
            return response()->json([
                'error' => "{$pickedPlayer->full_name} refuse de jouer pour ton équipe (relation en rupture avec le coach).",
            ], 422);
        }

        $pick = $draftService->executePick($gameSave, $data['player_id']);

        if (!$pick) {
            return response()->json(['error' => 'Invalid pick (player taken, too expensive, or squad full)'], 422);
        }

        $gameSave->refresh();
        $draftState = $gameSave->state['draft'] ?? [];

        if ($draftState['completed'] ?? false) {
            $draftService->finalizeDraft($gameSave);
        }

        return response()->json([
            'pick'       => $pick,
            'draftState' => $draftState,
            'completed'  => $draftState['completed'] ?? false,
        ]);
    }

    public function finish(Request $request, GameSave $gameSave)
    {
        $this->authorizeGameSave('update', $gameSave);

        $draftService = app(DraftService::class);
        $allDone = $draftService->finishTeamDraft($gameSave, $gameSave->controlled_game_team_id);

        $gameSave->refresh();

        return response()->json([
            'draftState' => $gameSave->state['draft'] ?? [],
            'completed'  => $allDone || ($gameSave->state['draft']['completed'] ?? false),
        ]);
    }
}

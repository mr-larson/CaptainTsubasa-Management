<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Resources\GameSaveResource;
use App\Http\Requests\GameSaves\PickDraftPlayerRequest;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\PlayerLink;
use App\Services\DraftAIService;
use App\Services\DraftService;
use App\Services\SeasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameDraftController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /**
     * Écran de draft (initial ou intersaison).
     */
    public function show(Request $request, GameSave $gameSave): Response|RedirectResponse
    {
        $this->authorizeGameSave('view', $gameSave);

        if (!$gameSave->isDraftPhase()) {
            return redirect()->route('game-saves.Play', $gameSave);
        }

        $gameTeams = GameTeam::with(['contracts.gamePlayer'])
            ->where('game_save_id', $gameSave->id)
            ->orderBy('name')
            ->get();

        $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts')
            ->excludingFictional()
            ->visibleForConfig($gameSave)
            ->orderBy('lastname')
            ->get();

        $draftService = app(DraftService::class);
        $weeks = SeasonService::lengthForTeamCount($gameTeams->count());
        foreach ($freePlayers as $player) {
            $player->draft_cost = $draftService->pickCost($gameSave, $player->adjusted_cost, $weeks);
        }

        return Inertia::render('GameSaves/Draft', [
            'gameSave'         => GameSaveResource::make($gameSave)->toArray(),
            'teams'            => $gameTeams,
            'freePlayers'      => $freePlayers,
            'draftState'        => $gameSave->gameState()->draft(),
            'controlledTeamId'  => $gameSave->controlled_game_team_id,
            'controlledTeamIds' => $gameSave->controlledGameTeamIds(),
            // Duos d'alchimie (une-deux), par base_player_id : pour badger les
            // joueurs liés dans le tableau de draft.
            'duoLinks'          => PlayerLink::linksWithNamesFor(
                $freePlayers->pluck('base_player_id')->filter()->unique()->values()->all()
            ),
        ]);
    }

    /**
     * Exécute un pick IA (appelé par le frontend pour chaque tour IA).
     */
    public function aiPick(Request $request, GameSave $gameSave)
    {
        $this->authorizeGameSave('update', $gameSave);

        // Verrou par save : deux picks concurrents ne peuvent pas signer le même
        // joueur ni consommer deux fois le même tour.
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) {
            if (!$gameSave->isDraftPhase()) {
                return response()->json(['error' => 'Not in draft phase'], 400);
            }

            $draftService = app(DraftService::class);

            // Vérifier que ce n'est PAS le tour du joueur humain
            if ($draftService->isHumanTurn($gameSave)) {
                return response()->json(['error' => 'It is your turn to pick'], 400);
            }

            if (!$gameSave->gameState()->hasDraft() || $gameSave->gameState()->draftCompleted()) {
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

            if ($aiService->shouldFinishDraft($team, $squadSize, DraftService::budgetFor($gameSave, $team))) {
                $draftService->finishTeamDraft($gameSave, $team->id);
                $gameSave->refresh();
                $draftState = $gameSave->gameState()->draft() ?? [];

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
                // Aucun joueur trouvable → cette équipe a fini (budget insuffisant),
                // même sous MIN_SQUAD : sinon le tour ne passe jamais.
                $draftService->finishTeamDraft($gameSave, $team->id, force: true);
                $gameSave->refresh();
                $draftState = $gameSave->gameState()->draft() ?? [];

                return response()->json([
                    'pick'       => null,
                    'finished'   => true,
                    'team_name'  => $team->name,
                    'draftState' => $draftState,
                    'completed'  => $draftState['completed'] ?? false,
                ]);
            }

            $pick = $draftService->executePick($gameSave, $playerId);
            if ($pick === null) {
                // Pick refusé (joueur pris, trop cher…) : l'IA perd son tour
                // plutôt que de bloquer le draft sur le même pointeur.
                $draftService->advancePick($gameSave);
            }
            $gameSave->refresh();

            $draftState = $gameSave->gameState()->draft() ?? [];

            if ($draftState['completed'] ?? false) {
                $draftService->finalizeDraft($gameSave);
            }

            return response()->json([
                'pick'       => $pick,
                'draftState' => $draftState,
                'completed'  => $draftState['completed'] ?? false,
            ]);
        });
    }

    /**
     * Le joueur humain pioche un joueur.
     */
    public function pick(PickDraftPlayerRequest $request, GameSave $gameSave)
    {
        $this->authorizeGameSave('update', $gameSave);

        $data = $request->validated();

        // Verrou par save : deux picks concurrents ne peuvent pas signer le même
        // joueur ni consommer deux fois le même tour.
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($data) {
            if (!$gameSave->isDraftPhase()) {
                return response()->json(['error' => 'Not in draft phase'], 400);
            }

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
                return response()->json(['error' => 'Sélection impossible : joueur déjà pris, trop cher ou effectif complet.'], 422);
            }

            $gameSave->refresh();
            $draftState = $gameSave->gameState()->draft() ?? [];

            if ($draftState['completed'] ?? false) {
                $draftService->finalizeDraft($gameSave);
            }

            return response()->json([
                'pick'       => $pick,
                'draftState' => $draftState,
                'completed'  => $draftState['completed'] ?? false,
            ]);
        });
    }

    public function finish(Request $request, GameSave $gameSave)
    {
        $this->authorizeGameSave('update', $gameSave);

        // Verrou par save : deux picks concurrents ne peuvent pas signer le même
        // joueur ni consommer deux fois le même tour.
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) {
            if (!$gameSave->isDraftPhase()) {
                return response()->json(['error' => 'Not in draft phase'], 400);
            }

            $draftService = app(DraftService::class);

            // Clôturer l'équipe humaine actuellement sur l'horloge (hot-seat), avec
            // repli sur l'équipe propriétaire si l'horloge n'est pas sur un humain.
            $current = $draftService->getCurrentTeamId($gameSave);
            $teamId = ($current !== null && in_array((int) $current, $gameSave->controlledGameTeamIds(), true))
                ? (int) $current
                : (int) $gameSave->controlled_game_team_id;

            // Sous le minimum d'effectif, on ne peut clôturer que si plus aucun
            // joueur n'est abordable : sinon le manager resterait bloqué au draft.
            $team  = GameTeam::where('game_save_id', $gameSave->id)->find($teamId);
            $force = $team && !$draftService->hasAffordablePlayer($gameSave, $team);

            $allDone = $draftService->finishTeamDraft($gameSave, $teamId, force: $force);

            $gameSave->refresh();

            return response()->json([
                'draftState' => $gameSave->gameState()->draft() ?? [],
                'completed'  => $allDone || $gameSave->gameState()->draftCompleted(),
            ]);
        });
    }
}

<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Http\Controllers\Controller;
use App\Http\Requests\GameSaves\PickDraftPlayerRequest;
use App\Http\Resources\GameSaveResource;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\PlayerLink;
use App\Services\Draft\DraftService;
use App\Services\Draft\DraftTurnService;
use App\Services\Season\SeasonService;
use App\Support\DraftTurnResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * DRAFT (début de partie ou intersaison).
 *
 * Pendant le draft, les équipes piochent tour à tour des joueurs libres. Ce
 * contrôleur gère les quatre actions de l'écran de draft :
 *
 *   - show()    affiche l'écran de draft ;
 *   - aiPick()  joue le tour d'une équipe IA (le navigateur l'appelle à chaque tour IA) ;
 *   - pick()    enregistre le choix d'un manager humain ;
 *   - finish()  clôt le draft d'une équipe humaine.
 *
 * Les trois actions d'écriture répondent en JSON (le draft se joue sans recharger la
 * page). Leur logique est dans App\Services\Draft\DraftTurnService ; les règles de fond
 * (ordre des tours, coûts, finalisation) dans App\Services\Draft\DraftService.
 */
class GameDraftController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /**
     * Affiche l'écran de draft : équipes et effectifs, joueurs libres avec leur coût de
     * pick, état du draft (ordre, picks, budgets) et managers humains.
     *
     * Si la partie n'est plus en phase de draft (ex. lien ancien), renvoie à la page de jeu.
     */
    public function show(Request $request, GameSave $gameSave): Response|RedirectResponse
    {
        $this->authorizeGameSave('view', $gameSave);

        if (! $gameSave->isDraftPhase()) {
            return redirect()->route('game-saves.Play', $gameSave);
        }

        $gameTeams = GameTeam::with(['contracts.gamePlayer'])
            ->where('game_save_id', $gameSave->id)
            ->orderBy('name')
            ->get();

        // Joueurs libres : ceux qui n'ont aucun contrat (hors fictifs, selon les origines visibles).
        $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts')
            ->excludingFictional()
            ->visibleForConfig($gameSave)
            ->orderBy('lastname')
            ->get();

        // Coût de pick de chaque joueur (salaire × durée de la saison, selon les réglages).
        $draftService = app(DraftService::class);
        $weeks        = SeasonService::lengthForTeamCount($gameTeams->count());
        foreach ($freePlayers as $player) {
            $player->draft_cost = $draftService->pickCost($gameSave, $player->adjusted_cost, $weeks);
        }

        return Inertia::render('GameSaves/Draft', [
            'gameSave'          => GameSaveResource::make($gameSave)->toArray(),
            'teams'             => $gameTeams,
            'freePlayers'       => $freePlayers,
            'draftState'        => $gameSave->gameState()->draft(),
            'controlledTeamId'  => $gameSave->controlled_game_team_id,
            'controlledTeamIds' => $gameSave->controlledGameTeamIds(),
            // Duos d'alchimie (« une-deux »), pour afficher un badge sur les joueurs liés.
            'duoLinks'          => PlayerLink::linksWithNamesFor(
                $freePlayers->pluck('base_player_id')->filter()->unique()->values()->all()
            ),
        ]);
    }

    /**
     * Joue le tour de l'équipe IA actuellement à son tour (réponse JSON).
     *
     * Le navigateur appelle cette action pour chaque tour IA. Selon le cas, la réponse
     * contient le joueur choisi, « finished » si l'équipe a clos son draft, ou « completed »
     * si tout le draft est terminé (cf. DraftTurnService::playAiTurn).
     */
    public function aiPick(Request $request, GameSave $gameSave): JsonResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        return $this->withSaveLock($gameSave, fn (GameSave $locked) => $this->json(
            app(DraftTurnService::class)->playAiTurn($locked)
        ));
    }

    /**
     * Enregistre le choix du manager humain dont c'est le tour (réponse JSON).
     * Le joueur choisi est validé par PickDraftPlayerRequest ; les refus (pas son tour,
     * joueur déjà pris, trop cher, joueur qui refuse l'équipe) sont expliqués par le
     * service (DraftTurnService::playHumanPick).
     */
    public function pick(PickDraftPlayerRequest $request, GameSave $gameSave): JsonResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        $playerId = (int) $request->validated()['player_id'];

        return $this->withSaveLock($gameSave, fn (GameSave $locked) => $this->json(
            app(DraftTurnService::class)->playHumanPick($locked, $playerId)
        ));
    }

    /**
     * Clôt le draft de l'équipe humaine actuellement à son tour (réponse JSON : état du
     * draft et « completed »). Sous l'effectif minimum, la clôture n'est forcée que si plus
     * aucun joueur n'est abordable (cf. DraftTurnService::finishHumanDraft).
     */
    public function finish(Request $request, GameSave $gameSave): JsonResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        return $this->withSaveLock($gameSave, fn (GameSave $locked) => $this->json(
            app(DraftTurnService::class)->finishHumanDraft($locked)
        ));
    }

    // ──────────────────────────────────────────────────────────────

    /** Traduit le résultat d'une action de draft en réponse JSON (corps + code HTTP). */
    private function json(DraftTurnResult $result): JsonResponse
    {
        return response()->json($result->body, $result->status);
    }
}

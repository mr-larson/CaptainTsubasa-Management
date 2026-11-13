<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Resources\GameSaveResource;
use App\Http\Requests\GameSaves\UpdateGameConfigRequest;
use App\Enums\GamePhase;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Http\Requests\GameSaves\GameSaveRequest;
use App\Models\GameSaves\GameSave;
use App\Services\CareerObjectiveService;
use App\Services\DraftService;
use App\Services\PlayPageBuilder;
use App\Services\SeasonBootstrapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PARTIES : liste, page de jeu, renommage, suppression et configuration.
 *
 *   - index()        liste des parties de l'utilisateur ;
 *   - continue()     ouvre la dernière partie ;
 *   - play()         page de jeu principale (données construites par PlayPageBuilder) ;
 *   - update()       renomme une partie ;
 *   - updateConfig() modifie la configuration en cours de partie ;
 *   - destroy()      supprime une partie.
 *
 * La CRÉATION d'une partie est dans GameSaveCreationController. Les données de la
 * page de jeu sont construites par App\Services\PlayPageBuilder.
 */
class GameSaveController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /**
     * Liste des parties de l'utilisateur (la plus récemment modifiée en premier).
     * On n'envoie que le résumé de chaque partie, sans son état détaillé.
     */
    public function index(Request $request): Response
    {
        $gameSaves = GameSave::where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (GameSave $save) => GameSaveResource::summary($save)->toArray());

        return Inertia::render('GameSaves/Index', [
            'gameSaves' => $gameSaves,
        ]);
    }

    /**
     * Page de jeu principale d'une partie (« dashboard »).
     *
     * Déroulé :
     *   1. redirige si la partie n'a pas sa page de jeu : carrière terminée, draft en
     *      cours ou fin de saison (chacune a son propre écran) ;
     *   2. filet de sécurité pour les anciennes parties : prépare la saison si besoin,
     *      sous le verrou de la partie (en régime normal, aucune écriture) ;
     *   3. tire la boutique de cartes de la semaine si elle est périmée (sous verrou) ;
     *   4. envoie à la page toutes ses données, construites par PlayPageBuilder.
     */
    public function play(Request $request, GameSave $gameSave, PlayPageBuilder $page): Response|RedirectResponse
    {
        $this->authorizeGameSave('view', $gameSave);

        // Carrière terminée (licencié / titre de carrière) : la session est figée.
        if (app(CareerObjectiveService::class)->isGameOver($gameSave)) {
            return redirect()->route('game-saves.game-over', $gameSave);
        }

        // Draft en cours (onglet fermé, draft interrompu) : on y retourne au lieu
        // d'ouvrir la page avec des effectifs partiels. Idem en fin de saison.
        if ($gameSave->isDraftPhase()) {
            return redirect()->route('game-saves.draft', $gameSave);
        }
        if ($gameSave->currentPhase() === GamePhase::SeasonEnd) {
            return redirect()->route('game-saves.season-end', $gameSave);
        }

        // Filet de sécurité pour les parties antérieures aux transitions explicites
        // (SeasonBootstrapService) : on ne prend le verrou que si quelque chose manque,
        // et on revérifie sous verrou. En régime normal, aucune écriture.
        $bootstrap = app(SeasonBootstrapService::class);
        if ($bootstrap->needsPreparation($gameSave)) {
            $this->withSaveLock($gameSave, function (GameSave $locked) use ($bootstrap) {
                if (! $bootstrap->needsPreparation($locked)) {
                    return;
                }

                app(DraftService::class)->purgeFinishedDraft($locked);
                $bootstrap->prepare($locked);
            });
        }

        // Boutique de la semaine : tirée au premier affichage, donc sous verrou (seulement
        // quand elle est périmée) ; la lecture, elle, reste libre.
        if ($page->shopNeedsRefresh($gameSave)) {
            $this->withSaveLock($gameSave, fn (GameSave $locked) => $page->refreshShop($locked));
        }

        return Inertia::render('GameSaves/Play', $page->build($gameSave));
    }

    /**
     * Renomme une partie. Seul le libellé est modifiable par le navigateur : semaine,
     * saison, équipe et état sont pilotés par le serveur (un état renvoyé par le
     * navigateur serait périmé et écraserait les changements faits depuis l'affichage).
     */
    public function update(GameSaveRequest $request, GameSave $gameSave): mixed
    {
        $this->authorizeGameSave('update', $gameSave);
        // Seul le libellé est modifiable par le client : semaine, saison, équipe
        // et state sont pilotés par le serveur (un state renvoyé par le client
        // est périmé et écraserait les changements faits depuis l'affichage).
        $gameSave->fill($request->safe()->only(['label']))->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Sauvegarde mise à jour.');
    }

    /** Supprime définitivement une partie (réservé à son propriétaire ou à un administrateur). */
    public function destroy(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeGameSave('delete', $gameSave);
        $gameSave->delete();

        return redirect()->route('game-saves.index')->with('success', 'Sauvegarde supprimée.');
    }

    /** Bouton « Continuer » : ouvre la partie la plus récemment modifiée, ou renvoie à la création s'il n'y en a pas. */
    public function continue(Request $request): RedirectResponse
    {
        $lastSave = GameSave::where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->first();

        if (!$lastSave) {
            return redirect()->route('game-saves.create')->with('info', 'Aucune partie existante.');
        }

        return redirect()->route('game-saves.Play', $lastSave);
    }

    /**
     * Modifie la configuration d'une partie en cours (règles de match, entraînement,
     * cartes…). Les options de départ ne sont pas modifiables (cf. UpdateGameConfigRequest).
     * Faite sous verrou : la configuration vit dans l'état de la partie, relu et réécrit en entier.
     */
    public function updateConfig(UpdateGameConfigRequest $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        $data = $request->configData();

        // Sous verrou : state.config est relu et réécrit avec tout le state.
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($data) {
            $gameSave->setConfig($data);

            return back()->with('success', 'Configuration mise à jour.');
        });
    }
}

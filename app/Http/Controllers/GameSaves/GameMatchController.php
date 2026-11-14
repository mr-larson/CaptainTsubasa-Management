<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Requests\GameSaves\FinishMatchRequest;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameSanction;
use App\Services\Matches\MatchResultService;
use App\Services\Matches\MatchSimulationService;
use App\Services\Matches\MatchScreenBuilder;
use App\Services\Season\WeekAdvanceService;
use App\Services\Season\WeekClosingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MATCHS D'UNE PARTIE.
 *
 * Ce contrôleur gère tout ce qui touche au déroulé des matchs :
 *
 *   - match()                     écran de match jouable (le moteur tourne dans le navigateur) ;
 *   - finishMatch()               enregistre le résultat d'un match joué à la main ;
 *   - simulateMatch()             simule le match de l'équipe active côté serveur ;
 *   - simulateWeek()              simule toute la semaine puis la clôture ;
 *   - stats() / weekLeaders()     données JSON du calendrier (détail d'un match, classements) ;
 *   - useCaptainReroll() / resetCaptainRerollActionFlag()   relances de capitaine pendant le match.
 *
 * La logique métier est dans les services :
 *   - enregistrement d'un résultat → MatchResultService ;
 *   - « simuler mon match »         → MatchSimulationService (qui s'appuie sur MatchSimulator) ;
 *   - avancer / clôturer la semaine → WeekAdvanceService puis WeekClosingService.
 */
class GameMatchController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /**
     * Affiche l'écran de match jouable : prépare le prochain match de l'équipe active
     * puis envoie au moteur de match (navigateur) sa configuration de départ.
     *
     * Déroulé :
     *   1. redirige vers la page de jeu si une pause de mi-saison est en attente ;
     *   2. trouve l'équipe jouée (manager actif en hot-seat) et son prochain match ;
     *   3. remet à 3 les relances de capitaine des deux équipes ;
     *   4. fait ajuster la composition IA du jour (blessés, fatigue), sous le verrou de la partie ;
     *   5. envoie la configuration à la page « Match/Engine ».
     *
     * Toute la préparation est dans App\Services\Matches\MatchScreenBuilder.
     */
    public function match(Request $request, GameSave $gameSave, MatchScreenBuilder $screen): Response|RedirectResponse
    {
        $this->authorizeGameSave('view', $gameSave);

        if ($gameSave->hasPendingMidseasonBreak()) {
            return redirect()->route('game-saves.Play', $gameSave);
        }

        $week  = (int) ($gameSave->week ?? 1);
        $team  = $screen->activeTeam($gameSave);
        $match = $screen->nextMatch($gameSave, $team, $week);

        $screen->resetCaptainRerolls($gameSave, $match);

        // La composition IA ne s'ajuste que pour un match de CETTE semaine, sous verrou
        // (c'est une écriture : deux onglets ne doivent pas l'appliquer en même temps).
        if ((int) $match->week === $week) {
            $this->withSaveLock($gameSave, fn (GameSave $locked) => $screen->adjustOpponentLineups($locked, $match, $week));
        }

        // Mode de contrôle demandé dans l'URL (?controlMode=both|single) ; une valeur invalide → « single ».
        $requestedMode = $request->query('controlMode', 'single');

        return Inertia::render('Match/Engine', [
            'engineConfig' => $screen->engineConfig($gameSave, $match, $team, is_string($requestedMode) ? $requestedMode : 'single'),
        ]);
    }

    /**
     * Détail d'un match déjà joué (statistiques, déroulé, effectifs, cartons et
     * blessures), en JSON. Chargé À LA DEMANDE par le calendrier : la page de jeu ne
     * reçoit pas ces données (trop lourdes). C'est le seul endroit où le déroulé du
     * match (game_match_events) est lu.
     */
    public function stats(GameSave $gameSave, GameMatch $match): JsonResponse
    {
        $this->authorizeGameSave('view', $gameSave, $match);

        // Cartons et blessures de ce match (source fiable pour la feuille de match,
        // y compris pour les matchs simulés qui n'ont pas ces événements dans le déroulé).
        $incidents = [
            'sanctions' => GameSanction::where('game_match_id', $match->id)
                ->get(['game_player_id', 'type', 'weeks_suspended', 'week_return'])
                ->map(fn ($s) => [
                    'player_id'       => (int) $s->game_player_id,
                    'type'            => $s->type,
                    'weeks_suspended' => (int) $s->weeks_suspended,
                    'week_return'     => $s->week_return,
                ])->values(),
            'injuries' => GameInjury::where('game_match_id', $match->id)
                ->get(['game_player_id', 'description', 'weeks_out', 'week_return'])
                ->map(fn ($i) => [
                    'player_id'   => (int) $i->game_player_id,
                    'description' => $i->description,
                    'weeks_out'   => (int) $i->weeks_out,
                    'week_return' => $i->week_return,
                ])->values(),
        ];

        return response()->json([
            'match_stats' => $match->fullStats(),
            'incidents' => $incidents,
        ]);
    }

    /**
     * Classements d'une journée, en JSON (onglet Calendrier) : homme du match,
     * buteurs, passeurs, dribbleurs, gardiens, défenseurs, duels.
     */
    public function weekLeaders(GameSave $gameSave, int $week): JsonResponse
    {
        $this->authorizeGameSave('view', $gameSave);

        return response()->json(app(\App\Services\Matches\WeekLeadersService::class)->forWeek($gameSave, $week));
    }

    /**
     * Enregistre le résultat d'un match que le manager vient de JOUER À LA MAIN
     * (le moteur de match tourne dans le navigateur et envoie le résultat ici).
     *
     * Déroulé :
     *   1. contrôles : le match appartient bien à la partie, les deux scores sont fournis ;
     *   2. sous le verrou de la partie, on ignore la demande si le match est déjà joué
     *      (double clic) et on refuse un match d'une semaine future ;
     *   3. MatchResultService enregistre score, classement, fautes, progression, etc. ;
     *   4. on enchaîne : un autre manager doit jouer, ou la semaine est clôturée.
     *
     * Toute la logique de l'enregistrement est dans App\Services\Matches\MatchResultService.
     */
    public function finishMatch(FinishMatchRequest $request, GameSave $gameSave, GameMatch $match, MatchResultService $results, WeekAdvanceService $weeks): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        abort_unless((int) $match->game_save_id === (int) $gameSave->id, 404);

        $data   = $request->validated();
        $scores = $data['scoresByTeamId'];

        // Le moteur doit fournir le score des DEUX équipes de ce match.
        $homeTeamId = (int) $match->home_team_id;
        $awayTeamId = (int) $match->away_team_id;
        if (! array_key_exists($homeTeamId, $scores) || ! array_key_exists($awayTeamId, $scores)) {
            abort(422, 'Scores incomplets pour ce match.');
        }

        // Verrou par partie : un double clic ou un rejeu attend la première requête, puis
        // retombe sur le match déjà joué au lieu de recompter classement, revenus et semaine.
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($match, $data, $scores, $homeTeamId, $awayTeamId, $results, $weeks) {
            $match->refresh();

            if (! $results->canStillBeRecorded($gameSave, $match)) {
                return redirect()->route('game-saves.Play', $gameSave);
            }
            abort_if($results->isScheduledForFutureWeek($gameSave, $match), 422, 'Ce match n\'est pas encore programmé.');

            $results->record(
                $gameSave,
                $match,
                (int) $scores[$homeTeamId],
                (int) $scores[$awayTeamId],
                $data['match_stats'] ?? null,
                $data['foulEvents'] ?? [],
            );

            return $this->redirectAfterWeek($gameSave, $weeks->advanceAfterMatch($gameSave, (int) $match->week));
        });
    }

    /**
     * SIMULE (par le serveur) le prochain match de l'équipe active, sans le jouer à la main.
     *
     * Le verrou de la partie évite qu'un double clic simule le match deux fois. Toutes les
     * règles sont dans App\Services\Matches\MatchSimulationService, qui répond par l'une de ces
     * issues :
     *   - interdit (403) : l'équipe active ne joue pas ce match, ou l'adversaire est aussi
     *     dirigé par un humain ;
     *   - rien à simuler : retour à l'écran indiqué (match déjà joué, partie terminée…) ;
     *   - match simulé : on enchaîne (autre manager à faire jouer, ou clôture de la semaine)
     *     avec un message résumant le score.
     */
    public function simulateMatch(GameSave $gameSave, GameMatch $match, MatchSimulationService $simulation): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $match);

        return $this->withSaveLock($gameSave, function (GameSave $save) use ($match, $simulation) {
            $result = $simulation->simulateNextMatch($save, $match);

            abort_if($result->isForbidden(), 403, (string) $result->forbiddenMessage);

            $redirect = $this->redirectAfterWeek($save, $result->outcome);

            return $result->summary !== null
                ? $redirect->with('success', "Match simulé · {$result->summary}")
                : $redirect;
        });
    }

    /**
     * Simule TOUTE la semaine courante : les matchs de toutes les équipes IA, puis
     * clôture de la semaine (revenus, entraînement IA, transferts, fin de saison éventuelle).
     *
     * Le navigateur envoie la semaine affichée (« week », pour qu'un double clic ne simule
     * pas deux semaines) et, au besoin, « midseason_break » pour valider la pause de
     * mi-saison. Le verrou de la partie sérialise les requêtes ; toutes les règles
     * (phase, partie terminée, hot-seat, pause) sont dans App\Services\Season\WeekAdvanceService.
     */
    public function simulateWeek(Request $request, GameSave $gameSave, WeekAdvanceService $weeks): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        $displayedWeek = $request->filled('week') ? (int) $request->input('week') : null;

        return $this->withSaveLock($gameSave, fn (GameSave $locked) => $this->redirectAfterWeek(
            $locked,
            $weeks->simulateWeek($locked, $displayedWeek, $request->boolean('midseason_break')),
        ));
    }

    /**
     * Choisit l'écran à afficher après une action qui fait avancer la semaine : partie
     * terminée, fin de saison, ou simple retour à la page de jeu (constantes
     * WeekClosingService::OUTCOME_*, renvoyées par WeekAdvanceService).
     */
    private function redirectAfterWeek(GameSave $gameSave, string $outcome): RedirectResponse
    {
        return match ($outcome) {
            WeekClosingService::OUTCOME_GAME_OVER  => redirect()->route('game-saves.game-over', $gameSave),
            WeekClosingService::OUTCOME_SEASON_END => redirect()->route('game-saves.season-end', $gameSave),
            default                                => redirect()->route('game-saves.Play', $gameSave),
        };
    }

    /**
     * Décompte une relance de capitaine pendant le match (appelée par le moteur, en JSON).
     * Répond 400 s'il n'en reste plus.
     */
    public function useCaptainReroll(Request $request, GameSave $gameSave, GameContract $contract): JsonResponse
    {
        $this->authorizeGameSave('update', $gameSave, $contract);

        if (! $contract->useReroll()) {
            return response()->json([
                'success' => false,
                'message' => 'Reroll indisponible.',
            ], 400);
        }

        return response()->json([
            'success'           => true,
            'rerollsRemaining'  => $contract->captain_rerolls_remaining,
        ]);
    }

    /**
     * Réarme l'indicateur « relance déjà utilisée sur cette action » du capitaine
     * (appelée par le moteur au début de chaque nouvelle action, en JSON).
     */
    public function resetCaptainRerollActionFlag(Request $request, GameSave $gameSave, GameContract $contract): JsonResponse
    {
        $this->authorizeGameSave('update', $gameSave, $contract);

        $contract->resetRerollActionFlag();

        return response()->json(['success' => true]);
    }
}

<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Models\GameSaves\GameSave;
use App\Services\CareerObjectiveService;
use App\Services\SeasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameSeasonController extends Controller
{
    use AuthorizesGameSave;

    /**
     * Écran de fin de saison : champion, MVP, primes de classement.
     */
    public function show(Request $request, GameSave $gameSave): Response
    {
        $this->authorizeGameSave('view', $gameSave);

        if ($gameSave->phase !== 'season_end') {
            return redirect()->route('game-saves.Play', $gameSave);
        }

        $state = $gameSave->state ?? [];

        return Inertia::render('GameSaves/SeasonEnd', [
            'gameSave' => $gameSave,
            'recap'    => $state['last_season_recap'] ?? null,
        ]);
    }

    /**
     * Lance la saison suivante : expiration des contrats, reset du classement,
     * et démarrage d'une nouvelle draft (ordre inverse du classement final).
     */
    public function continue(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        if ($gameSave->phase !== 'season_end') {
            return redirect()->route('game-saves.Play', $gameSave);
        }

        // Carrière terminée (licencié ou objectif long terme atteint) : pas de
        // saison suivante, on bascule sur le bilan de carrière.
        if (app(CareerObjectiveService::class)->isGameOver($gameSave)) {
            return redirect()->route('game-saves.game-over', $gameSave);
        }

        app(SeasonService::class)->startNewSeason($gameSave);

        return redirect()->route('game-saves.draft', $gameSave)
            ->with('success', "Saison {$gameSave->season} — la draft commence !");
    }

    /**
     * Bilan de carrière : écran de fin de partie (licenciement ou victoire).
     */
    public function gameOver(Request $request, GameSave $gameSave): Response|RedirectResponse
    {
        $this->authorizeGameSave('view', $gameSave);

        $career = app(CareerObjectiveService::class);
        if (! $career->isGameOver($gameSave)) {
            return redirect()->route('game-saves.Play', $gameSave);
        }

        $data  = $career->data($gameSave);
        $state = $gameSave->state ?? [];

        return Inertia::render('GameSaves/GameOver', [
            'gameSave' => $gameSave,
            'career'   => [
                'status'          => $data['status'],
                'fired_reason'    => $data['fired_reason'],
                'confidence'      => (int) $data['confidence'],
                'titles_won'      => (int) $data['titles_won'],
                'titles_required' => (int) $data['titles_required'],
                'difficulty'      => $data['difficulty'],
                'history'         => array_values($data['history'] ?? []),
                'last_verdict'    => $data['last_verdict'],
            ],
            'lastRecap' => $state['last_season_recap'] ?? null,
        ]);
    }
}

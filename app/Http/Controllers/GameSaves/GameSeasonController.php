<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Models\GameSaves\GameSave;
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

        app(SeasonService::class)->startNewSeason($gameSave);

        return redirect()->route('game-saves.draft', $gameSave)
            ->with('success', "Saison {$gameSave->season} — la draft commence !");
    }
}

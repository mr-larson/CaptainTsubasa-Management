<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\CareerObjectiveService;
use App\Services\SeasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameSeasonController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /**
     * Écran de fin de saison : champion, MVP, primes de classement.
     */
    public function show(Request $request, GameSave $gameSave): Response|RedirectResponse
    {
        $this->authorizeGameSave('view', $gameSave);

        if ($gameSave->phase !== 'season_end') {
            return redirect()->route('game-saves.Play', $gameSave);
        }

        $state = $gameSave->state ?? [];

        return Inertia::render('GameSaves/SeasonEnd', [
            'gameSave' => $gameSave,
            'recap'    => $gameSave->gameState()->lastSeasonRecap(),
        ]);
    }

    /**
     * Lance la saison suivante : expiration des contrats, reset du classement,
     * et démarrage d'une nouvelle draft (ordre inverse du classement final).
     */
    public function continue(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        // Verrou par save : un double clic sur « continuer » repasse le test de
        // phase après la première requête et ne relance pas une 2e saison
        // (saison +2, bonus de draft payé deux fois).
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) {
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
        });
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
            'lastRecap' => $gameSave->gameState()->lastSeasonRecap(),
            'finalSeason' => $this->currentSeasonSummary($gameSave, $data),
        ]);
    }

    /**
     * Bilan de la saison en cours au moment de la fin du mandat (licenciement en
     * cours de saison) : rang, bilan, buts, semaine. Les matchs ne concernent que
     * la saison courante (le calendrier est purgé à chaque intersaison).
     */
    private function currentSeasonSummary(GameSave $gameSave, array $career): ?array
    {
        $teamId = (int) $gameSave->controlled_game_team_id;
        $teams  = GameTeam::where('game_save_id', $gameSave->id)->get(['id', 'name']);
        if (! $teamId || $teams->isEmpty()) return null;

        $rows = $teams->mapWithKeys(fn ($t) => [$t->id => ['id' => $t->id, 'name' => $t->name, 'pts' => 0, 'w' => 0, 'd' => 0, 'l' => 0, 'gf' => 0, 'ga' => 0]])->all();
        $played = GameMatch::where('game_save_id', $gameSave->id)->where('status', 'played')
            ->get(['id', 'week', 'home_team_id', 'away_team_id', 'home_score', 'away_score']);
        foreach ($played as $m) {
            $h = (int) $m->home_team_id; $a = (int) $m->away_team_id;
            if (! isset($rows[$h], $rows[$a])) continue;
            $hs = (int) $m->home_score; $as = (int) $m->away_score;
            $rows[$h]['gf'] += $hs; $rows[$h]['ga'] += $as;
            $rows[$a]['gf'] += $as; $rows[$a]['ga'] += $hs;
            if ($hs > $as)      { $rows[$h]['w']++; $rows[$h]['pts'] += 3; $rows[$a]['l']++; }
            elseif ($hs < $as)  { $rows[$a]['w']++; $rows[$a]['pts'] += 3; $rows[$h]['l']++; }
            else                { $rows[$h]['d']++; $rows[$a]['d']++; $rows[$h]['pts']++; $rows[$a]['pts']++; }
        }

        // Départage identique au classement du jeu : points, diff., buts, victoires, nom.
        $order = collect($rows)->sort(fn ($x, $y) =>
            [$y['pts'], $y['gf'] - $y['ga'], $y['gf'], $y['w'], $x['name']] <=> [$x['pts'], $x['gf'] - $x['ga'], $x['gf'], $x['w'], $y['name']]
        )->values();
        $me = $order->firstWhere('id', $teamId);
        if (! $me || ($me['w'] + $me['d'] + $me['l']) === 0) return null;

        return [
            'team'        => $me['name'],
            'season'      => (int) ($gameSave->season ?? 1),
            'last_week'   => (int) ($played->max('week') ?? 0),
            'rank'        => $order->search(fn ($r) => $r['id'] === $teamId) + 1,
            'team_count'  => $order->count(),
            'wins'        => $me['w'],
            'draws'       => $me['d'],
            'losses'      => $me['l'],
            'goals_for'   => $me['gf'],
            'goals_against' => $me['ga'],
            'points'      => $me['pts'],
            'objective'   => $career['mandate']['label'] ?? null,
            'target_rank' => $career['mandate']['target_rank'] ?? null,
        ];
    }
}

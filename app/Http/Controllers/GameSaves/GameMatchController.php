<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\AITrainingService;
use App\Services\MatchSimulator;
use App\Services\StaminaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GameMatchController extends Controller
{
    /**
     * Écran de match jouable pour une session.
     */
    public function match(Request $request, GameSave $gameSave): Response
    {
        $this->authorizeSave($request, $gameSave);

        $controlledGameTeam = GameTeam::where('game_save_id', $gameSave->id)
            ->where('base_team_id', $gameSave->team_id)
            ->firstOrFail();

        $match = GameMatch::with([
            'homeTeam.contracts.gamePlayer.basePlayer',
            'awayTeam.contracts.gamePlayer.basePlayer',
        ])
            ->where('game_save_id', $gameSave->id)
            ->where('status', 'scheduled')
            ->where('week', '>=', $gameSave->week ?? 1)
            ->where(function ($q) use ($controlledGameTeam) {
                $q->where('home_team_id', $controlledGameTeam->id)
                    ->orWhere('away_team_id', $controlledGameTeam->id);
            })
            ->orderBy('week')
            ->firstOrFail();

        $homeTeam = $match->homeTeam;
        $awayTeam = $match->awayTeam;

        $isControlledHome = ((int) $controlledGameTeam->id === (int) $homeTeam->id);

        $controlMode = $request->query('controlMode', 'both');
        if (!in_array($controlMode, ['both', 'single'], true)) {
            $controlMode = 'both';
        }

        $controlledSide = $request->query(
            'controlledSide',
            $isControlledHome ? 'internal' : 'external'
        );
        if (!in_array($controlledSide, ['internal', 'external'], true)) {
            $controlledSide = $isControlledHome ? 'internal' : 'external';
        }

        $internalTeam = $isControlledHome ? $homeTeam : $awayTeam;
        $externalTeam = $isControlledHome ? $awayTeam : $homeTeam;

        $state   = $gameSave->state ?? [];
        $lineups = $state['lineup'] ?? [];

        $internalFormation = $lineups[$internalTeam->id]['formation'] ?? '3-2-3-2';
        $externalFormation = $lineups[$externalTeam->id]['formation'] ?? '3-2-3-2';

        $homeLogoUrl = $homeTeam->logo_path ? '/' . ltrim($homeTeam->logo_path, '/') : null;
        $awayLogoUrl = $awayTeam->logo_path ? '/' . ltrim($awayTeam->logo_path, '/') : null;

        return Inertia::render('Match/Engine', [
            'engineConfig' => [
                'gameSaveId'     => $gameSave->id,
                'matchId'        => $match->id,
                'week'           => $match->week,
                'maxTurns'       => 40,
                'controlMode'    => $controlMode,
                'controlledSide' => $controlledSide,

                'homeTeamName' => $homeTeam->name,
                'awayTeamName' => $awayTeam->name,
                'homeLogoUrl'  => $homeLogoUrl,
                'awayLogoUrl'  => $awayLogoUrl,

                'homeTeamId' => $match->home_team_id,
                'awayTeamId' => $match->away_team_id,

                'sides' => [
                    'internalTeamId' => $internalTeam->id,
                    'externalTeamId' => $externalTeam->id,
                ],

                'teams' => [
                    'internal' => [
                        'id'        => $internalTeam->id,
                        'name'      => $internalTeam->name,
                        'logo_path' => $internalTeam->logo_path,
                        'formation' => $internalFormation,
                        'players'   => $this->mapPlayers($internalTeam, $gameSave),
                    ],
                    'external' => [
                        'id'        => $externalTeam->id,
                        'name'      => $externalTeam->name,
                        'logo_path' => $externalTeam->logo_path,
                        'formation' => $externalFormation,
                        'players'   => $this->mapPlayers($externalTeam, $gameSave),
                    ],
                ],
            ],
        ]);
    }

    /**
     * ✅ FINALISATION DU MATCH — VERSION CORRIGÉE
     */
    public function finishMatch(Request $request, GameSave $gameSave, GameMatch $match): RedirectResponse
    {
        $this->authorizeSave($request, $gameSave);

        abort_unless((int) $match->game_save_id === (int) $gameSave->id, 404);

        if ($match->status === 'played') {
            return redirect()->route('game-saves.play', $gameSave);
        }

        $data = $request->validate([
            'scoresByTeamId'   => ['required', 'array', 'min:2'],
            'scoresByTeamId.*' => ['integer', 'min:0'],
            'playerActions'    => ['array'],
            'match_stats'      => ['nullable', 'array'],
        ]);

        $scores     = $data['scoresByTeamId'];
        $homeTeamId = (int) $match->home_team_id;
        $awayTeamId = (int) $match->away_team_id;

        if (!isset($scores[$homeTeamId], $scores[$awayTeamId])) {
            abort(422, 'Scores incomplets pour ce match.');
        }

        $homeScore = (int) $scores[$homeTeamId];
        $awayScore = (int) $scores[$awayTeamId];

        /**
         * ✅ CORRECTION CLÉ :
         * Un match PLAYED ne peut JAMAIS avoir match_stats = NULL
         */
        $safeMatchStats = $data['match_stats'] ?? [
            'teams'   => [],
            'players' => [],
        ];

        if (!isset($safeMatchStats['players']) || !is_array($safeMatchStats['players'])) {
            $safeMatchStats['players'] = [];
        }

        if (!isset($safeMatchStats['teams']) || !is_array($safeMatchStats['teams'])) {
            $safeMatchStats['teams'] = [];
        }

        // 1. Sauvegarde du match
        $match->update([
            'home_score'  => $homeScore,
            'away_score'  => $awayScore,
            'status'      => 'played',
            'match_stats' => $safeMatchStats,
        ]);

        // 2. Classement
        $home = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($homeTeamId);
        $away = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($awayTeamId);

        if ($homeScore > $awayScore) {
            $home->wins++; $away->losses++;
        } elseif ($homeScore < $awayScore) {
            $away->wins++; $home->losses++;
        } else {
            $home->draws++; $away->draws++;
        }

        $home->save();
        $away->save();

        // 3. Autres matchs + entraînement
        app(MatchSimulator::class)->simulateOtherMatchesOfWeek($match);
        app(AITrainingService::class)->trainForWeek($gameSave);

        // 4. Avancer la semaine
        $gameSave->week = max($gameSave->week ?? 1, $match->week + 1);
        $gameSave->save();

        // 5. Historique des actions
        $state                   = $gameSave->state ?? [];
        $state['player_actions'] = array_merge(
            $state['player_actions'] ?? [],
            $data['playerActions'] ?? []
        );
        $gameSave->state = $state;
        $gameSave->save();

        // 6. Stamina
        StaminaService::applyAfterMatch($gameSave);

        return redirect()->route('game-saves.play', $gameSave);
    }

    /**
     * Simule tous les matchs de la semaine courante.
     */
    public function simulateWeek(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeSave($request, $gameSave);

        $week = (int) ($gameSave->week ?? 1);

        $matches = GameMatch::query()
            ->where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'scheduled')
            ->with(['homeTeam.contracts.gamePlayer', 'awayTeam.contracts.gamePlayer'])
            ->get();

        app(MatchSimulator::class)->simulateMatchesCollection($matches);
        app(AITrainingService::class)->trainForWeek($gameSave);

        $gameSave->week = $week + 1;
        $gameSave->save();

        return redirect()->route('game-saves.play', $gameSave);
    }

    // ==========================
    //   HELPERS PRIVÉS
    // ==========================

    private function mapPlayers(GameTeam $team, GameSave $gameSave)
    {
        $state      = $gameSave->state ?? [];
        $lineups    = $state['lineup'] ?? [];
        $teamLineup = $lineups[$team->id]['slots'] ?? null;

        $contracts = $team->contracts->loadMissing('gamePlayer');
        $starters  = $contracts->where('is_starter', true)->values();
        $ordered   = collect();

        if ($teamLineup) {
            for ($slot = 1; $slot <= 11; $slot++) {
                $pid = $teamLineup[$slot] ?? null;
                $c   = $pid ? $starters->firstWhere('game_player_id', $pid) : null;
                $ordered->push([$slot, $c]);
                if ($c) {
                    $starters = $starters->reject(fn ($cc) => $cc->id === $c->id)->values();
                }
            }
        } else {
            for ($slot = 1; $slot <= 11; $slot++) {
                $ordered->push([$slot, $starters[$slot - 1] ?? null]);
            }
        }

        return $ordered->map(function (array $row) {
            [$slot, $c] = $row;

            if (!$c || !$c->gamePlayer) {
                return [
                    'id' => null,
                    'number' => $slot,
                    'firstname' => '',
                    'lastname' => '',
                    'position' => '',
                    'is_starter' => false,
                    'photo_path' => null,
                    'photo_url' => null,
                    'stats' => null,
                    'special_moves' => [],
                ];
            }

            $p = $c->gamePlayer;

            return [
                'id'         => $p->id,
                'number'     => $slot,
                'firstname'  => $p->firstname,
                'lastname'   => $p->lastname,
                'position'   => $p->position,
                'is_starter' => (bool) $c->is_starter,
                'photo_path' => $p->photo_path,
                'photo_url'  => $p->photo_path ? Storage::url($p->photo_path) : null,
                'stats' => [
                    'speed' => $p->speed,
                    'stamina' => $p->stamina,
                    'attack' => $p->attack,
                    'defense' => $p->defense,
                    'shot' => $p->shot,
                    'pass' => $p->pass,
                    'dribble' => $p->dribble,
                    'block' => $p->block,
                    'intercept' => $p->intercept,
                    'tackle' => $p->tackle,
                    'hand_save' => $p->hand_save,
                    'punch_save' => $p->punch_save,
                ],
                'special_moves' => $p->special_moves ?? [],
            ];
        })->values();
    }

    private function authorizeSave(Request $request, GameSave $gameSave): void
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}

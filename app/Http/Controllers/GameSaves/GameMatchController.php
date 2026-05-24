<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\AITrainingService;
use App\Services\MatchSimulator;
use App\Services\AITransferService;
use App\Services\FoulAndInjuryService;
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

        // Par défaut : mode single, côté selon domicile/extérieur de l'équipe contrôlée
        $controlMode = $request->query('controlMode', 'single');
        if (!in_array($controlMode, ['both', 'single'], true)) {
            $controlMode = 'single';
        }

        $defaultSide    = $isControlledHome ? 'internal' : 'external';
        $controlledSide = $request->query('controlledSide', $defaultSide);
        if (!in_array($controlledSide, ['internal', 'external'], true)) {
            $controlledSide = $defaultSide;
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
     * Finalise un match joué manuellement.
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
            'foulEvents'       => ['nullable', 'array'],
        ]);

        $scores     = $data['scoresByTeamId'];
        $homeTeamId = (int) $match->home_team_id;
        $awayTeamId = (int) $match->away_team_id;

        if (!array_key_exists($homeTeamId, $scores) || !array_key_exists($awayTeamId, $scores)) {
            abort(422, 'Scores incomplets pour ce match.');
        }

        $homeScore = (int) $scores[$homeTeamId];
        $awayScore = (int) $scores[$awayTeamId];

        // 1. Sauvegarder le match avec match_stats (source de vérité)
        $match->home_score  = $homeScore;
        $match->away_score  = $awayScore;
        $match->status      = 'played';
        $match->match_stats = $data['match_stats'] ?? null;
        $match->save();

        // 2. Classement
        $home = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($homeTeamId);
        $away = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($awayTeamId);

        if ($homeScore > $awayScore)      { $home->wins++;   $away->losses++; }
        elseif ($homeScore < $awayScore)  { $away->wins++;   $home->losses++; }
        else                              { $home->draws++;  $away->draws++;  }

        $home->save();
        $away->save();

        // 3. Simuler les autres matchs de la semaine
        app(MatchSimulator::class)->simulateOtherMatchesOfWeek($match);
        app(AITrainingService::class)->trainForWeek($gameSave);
        app(AITransferService::class)->recruitForWeek($gameSave);

        // 4. Avancer la semaine
        $gameSave->week = max($gameSave->week ?? 1, $match->week + 1);
        $gameSave->save();

        // 5. Conserver player_actions dans le state (pour replay futur)
        $state                     = $gameSave->state ?? [];
        $state['player_actions']   = array_merge($state['player_actions'] ?? [], $data['playerActions'] ?? []);
        $gameSave->state           = $state;
        $gameSave->save();

        // 6. Stamina après match (source : match_stats du match joué)
        StaminaService::applyAfterMatch($gameSave, $match);

        // 7. Fautes, cartons et blessures
        $foulEvents = $data['foulEvents'] ?? [];
        app(FoulAndInjuryService::class)->processMatchEvents($gameSave, $match, $foulEvents);

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
        app(AITransferService::class)->recruitForWeek($gameSave);

        $gameSave->week = $week + 1;
        $gameSave->save();

        return redirect()->route('game-saves.play', $gameSave);
    }

    // ==========================
    //   HELPERS PRIVÉS
    // ==========================

    private function mapPlayers(GameTeam $team, GameSave $gameSave): \Illuminate\Support\Collection
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
                if ($pid) {
                    $c = $starters->firstWhere('game_player_id', $pid);
                    if ($c) {
                        $ordered->push([$slot, $c]);
                        $starters = $starters->reject(fn($cc) => $cc->id === $c->id)->values();
                        continue;
                    }
                }
                $ordered->push([$slot, null]);
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
                    'id' => null, 'number' => $slot,
                    'firstname' => '', 'lastname' => '', 'position' => '',
                    'is_starter' => false, 'photo_path' => null, 'photo_url' => null,
                    'stats' => null, 'special_moves' => [],
                ];
            }

            $p = $c->gamePlayer;
            return [
                'id'            => $p->id,
                'number'        => $slot,
                'firstname'     => $p->firstname,
                'lastname'      => $p->lastname,
                'position'      => $p->position,
                'is_starter'    => (bool) $c->is_starter,
                'photo_path'    => $p->photo_path,
                'photo_url'     => $p->photo_path ? Storage::url($p->photo_path) : null,
                'stats'         => [
                    'speed' => $p->speed, 'stamina' => $p->stamina,
                    'attack' => $p->attack, 'defense' => $p->defense,
                    'shot' => $p->shot, 'pass' => $p->pass, 'dribble' => $p->dribble,
                    'block' => $p->block, 'intercept' => $p->intercept, 'tackle' => $p->tackle,
                    'hand_save' => $p->hand_save, 'punch_save' => $p->punch_save,
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

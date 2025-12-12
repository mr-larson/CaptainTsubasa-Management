<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameSaveRequest;
use App\Services\MatchSimulator;
use App\Models\GameSave;
use App\Models\Team;
use App\Models\Player;
use App\Models\Contract;
use App\Models\GameTeam;
use App\Models\GamePlayer;
use App\Models\GameContract;
use App\Models\GameMatch;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GameSaveController extends Controller
{
    public function index(Request $request): Response
    {
        // Liste uniquement les sauvegardes du user connecté
        $gameSaves = GameSave::with('team')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('GameSaves/Index', [
            'gameSaves' => $gameSaves,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('GameSaves/Create');
    }

    /**
     * Étape 1 : on reçoit label + period
     * → on affiche l'écran de choix d'équipe (aucune sauvegarde créée pour l'instant).
     */
    public function store(Request $request): Response
    {
        $data = $request->validate([
            'label'  => ['nullable', 'string', 'max:255'],
            'period' => ['required', 'string', 'in:college'], // MVP : collège uniquement
        ]);

        // On charge les équipes avec leurs contrats + joueurs
        $teams = Team::with(['contracts.player'])
            ->orderBy('name')
            ->get();

        return Inertia::render('GameSaves/TeamSelection', [
            'label'  => $data['label'] ?? null,
            'period' => $data['period'],
            'teams'  => $teams,
        ]);
    }

    /**
     * Étape 2 : l'utilisateur a choisi une équipe et confirme.
     * Ici seulement on crée la première sauvegarde.
     */
    public function start(GameSaveRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $gameSave = GameSave::create([
            'user_id' => $request->user()->id,
            'team_id' => $data['team_id'], // team de base choisie
            'period'  => $data['period'],
            'season'  => 1,
            'week'    => 1,
            'label'   => $data['label'] ?? null,
            'state'   => null,
        ]);

        // -----------------------------
        // 1. Dupliquer toutes les équipes
        // -----------------------------
        $teams = Team::orderBy('id')->get();

        $teamCount    = $teams->count();
        $seasonLength = max(1, ($teamCount - 1) * 2);

        $gameTeamsByBaseId = [];

        foreach ($teams as $team) {
            $gameTeam = GameTeam::create([
                'game_save_id' => $gameSave->id,
                'base_team_id' => $team->id,
                'name'         => $team->name,
                'description'  => $team->description,
                'budget'       => $team->budget,
                'wins'         => 0,
                'draws'        => 0,
                'losses'       => 0,
            ]);

            $gameTeamsByBaseId[$team->id] = $gameTeam;
        }

        $controlled = $gameTeamsByBaseId[$data['team_id']] ?? null;
        if ($controlled) {
            $gameSave->controlled_game_team_id = $controlled->id;
            $gameSave->save();
        }


        // -----------------------------
        // 2. Dupliquer tous les joueurs
        // -----------------------------
        $players = Player::orderBy('id')->get();
        $gamePlayersByBaseId = [];

        foreach ($players as $player) {
            $stats = $player->stats ?? []; // si tu stockes en JSON

            $gamePlayer = GamePlayer::create([
                'game_save_id'   => $gameSave->id,
                'base_player_id' => $player->id,
                'firstname'      => $player->firstname,
                'lastname'       => $player->lastname,
                'position'       => $player->position,

                'speed'      => $player->speed      ?? $stats['speed']      ?? 50,
                'stamina'    => $player->stamina    ?? $stats['stamina']    ?? 50,
                'attack'     => $player->attack     ?? $stats['attack']     ?? 50,
                'defense'    => $player->defense    ?? $stats['defense']    ?? 50,

                'shot'       => $player->shot       ?? $stats['shot']       ?? 50,
                'pass'       => $player->pass       ?? $stats['pass']       ?? 50,
                'dribble'    => $player->dribble    ?? $stats['dribble']    ?? 50,
                'block'      => $player->block      ?? $stats['block']      ?? 50,
                'intercept'  => $player->intercept  ?? $stats['intercept']  ?? 50,
                'tackle'     => $player->tackle     ?? $stats['tackle']     ?? 50,

                'hand_save'  => $player->hand_save  ?? $stats['hand_save']  ?? 0,
                'punch_save' => $player->punch_save ?? $stats['punch_save'] ?? 0,

                'cost'       => $player->cost ?? 0,
            ]);

            $gamePlayersByBaseId[$player->id] = $gamePlayer;
        }

        // -----------------------------
        // 3. Dupliquer tous les contrats
        // -----------------------------
        $contracts = Contract::with(['team', 'player'])->get();

        foreach ($contracts as $contract) {
            $baseTeam   = $contract->team;
            $basePlayer = $contract->player;

            if (
                !isset($gameTeamsByBaseId[$baseTeam->id]) ||
                !isset($gamePlayersByBaseId[$basePlayer->id])
            ) {
                continue;
            }

            GameContract::create([
                'game_save_id'   => $gameSave->id,
                'game_team_id'   => $gameTeamsByBaseId[$baseTeam->id]->id,
                'game_player_id' => $gamePlayersByBaseId[$basePlayer->id]->id,
                'salary'         => $contract->salary ?? 0,
                'start_week'     => 1,
                'end_week'       => $seasonLength,
            ]);
        }

        return redirect()
            ->route('game-saves.play', $gameSave)
            ->with('success', 'Partie créée');
    }

    /**
     * Dashboard de la session.
     */
    public function play(Request $request, GameSave $gameSave): Response
    {
        $this->authorizeSave($request, $gameSave);

        $gameTeams = GameTeam::with(['contracts.gamePlayer'])
            ->where('game_save_id', $gameSave->id)
            ->orderBy('name')
            ->get();

        // charge la relation contrôlée
        $gameSave->load('controlledGameTeam');

        $controlledTeam = $gameTeams->firstWhere('base_team_id', $gameSave->team_id)
            ?? $gameTeams->first();

        $this->ensureCalendar($gameSave, $gameTeams);

        $matches = GameMatch::with(['homeTeam', 'awayTeam'])
            ->where('game_save_id', $gameSave->id)
            ->orderBy('week')
            ->get();

        $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        $gameTeams->loadMissing(['contracts.gamePlayer']);

        return Inertia::render('GameSaves/Play', [
            'gameSave'       => $gameSave,
            'teams'          => $gameTeams,
            'matches'        => $matches,
            'freePlayers'    => $freePlayers,
            'controlledTeam' => $controlledTeam,
        ]);
    }

    public function signFreeAgent(Request $request, GameSave $gameSave, GamePlayer $player): RedirectResponse
    {
        $this->authorizeSave($request, $gameSave);

        $data = $request->validate([
            'team_id'       => [
                'required',
                'integer',
                Rule::exists('game_teams', 'id')->where('game_save_id', $gameSave->id),
            ],
            'salary'        => ['required', 'integer', 'min:0'],
            'matches_total' => ['required', 'integer', 'min:1'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ]);

        $team = GameTeam::where('game_save_id', $gameSave->id)
            ->findOrFail($data['team_id']);

        // Déjà sous contrat ?
        $alreadyHasContract = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->exists();

        if ($alreadyHasContract) {
            return back()->with('info', 'Ce joueur a déjà un contrat en cours dans cette partie.');
        }

        // Création du contrat de partie
        $startWeek = $gameSave->week ?? 1;
        $endWeek   = $startWeek + $data['matches_total'] - 1;

        GameContract::create([
            'game_save_id'   => $gameSave->id,
            'game_team_id'   => $team->id,
            'game_player_id' => $player->id,
            'salary'         => $data['salary'],
            'start_week'     => $startWeek,
            'end_week'       => $endWeek,
            // si tu as une colonne matches_total dans game_contracts, ajoute-la ici
            // 'matches_total'  => $data['matches_total'],
        ]);

        // Mise à jour du budget
        $totalCost    = $data['salary'] * $data['matches_total'];
        $team->budget = max(0, ($team->budget ?? 0) - $totalCost);
        $team->save();

        // Log optionnel dans le state de GameSave (pour historique / RP)
        $state = $gameSave->state ?? [];
        $state['free_agent_signings'] = $state['free_agent_signings'] ?? [];
        $state['free_agent_signings'][] = [
            'player_id'      => $player->id,
            'team_id'        => $team->id,
            'salary'         => $data['salary'],
            'matches_total'  => $data['matches_total'],
            'total_cost'     => $totalCost,
            'reason'         => $data['reason'] ?? null,
            'week'           => $gameSave->week,
        ];
        $gameSave->state = $state;
        $gameSave->save();

        return back()->with('success', 'Joueur signé et budget mis à jour.');
    }

    private function ensureCalendar(GameSave $gameSave, Collection $teams): void
    {
        // S'il y a déjà un calendrier, on ne régénère pas
        if ($gameSave->matches()->exists()) {
            return;
        }

        // Ici, $teams est une collection de GameTeam
        $teamIds   = $teams->pluck('id')->values()->all();
        $teamCount = count($teamIds);

        if ($teamCount < 2) {
            return;
        }

        $hasGhost = false;
        if ($teamCount % 2 === 1) {
            $teamIds[] = null;
            $teamCount++;
            $hasGhost = true;
        }

        $rounds = $teamCount - 1;
        $half   = $teamCount / 2;
        $ids    = $teamIds;

        for ($round = 0; $round < $rounds; $round++) {
            $weekAller  = $round + 1;
            $weekRetour = $round + 1 + $rounds;

            for ($i = 0; $i < $half; $i++) {
                $home = $ids[$i];
                $away = $ids[$teamCount - 1 - $i];

                if ($home === null || $away === null) {
                    continue;
                }

                GameMatch::create([
                    'game_save_id' => $gameSave->id,
                    'week'         => $weekAller,
                    'home_team_id' => $home, // 👉 id de GameTeam
                    'away_team_id' => $away,
                    'status'       => 'scheduled',
                ]);

                GameMatch::create([
                    'game_save_id' => $gameSave->id,
                    'week'         => $weekRetour,
                    'home_team_id' => $away,
                    'away_team_id' => $home,
                    'status'       => 'scheduled',
                ]);
            }

            $fixed = array_shift($ids);
            $last  = array_pop($ids);
            array_unshift($ids, $fixed);
            array_splice($ids, 1, 0, [$last]);
        }
    }

    /**
     * Écran de match pour une session.
     */
    /**
     * Écran de match pour une session.
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

        $controlledGameTeam = GameTeam::where('game_save_id', $gameSave->id)
            ->where('base_team_id', $gameSave->team_id)
            ->first() ?? $homeTeam;

        $isControlledHome = ($controlledGameTeam->id === $homeTeam->id);

        $internalTeam = $isControlledHome ? $homeTeam : $awayTeam;
        $externalTeam = $isControlledHome ? $awayTeam : $homeTeam;

        $controlMode    = $request->query('controlMode', 'both');      // both|single
        $controlledSide = $request->query('controlledSide', 'internal'); // internal|external
        if (!in_array($controlMode, ['both','single'], true)) $controlMode = 'both';
        if (!in_array($controlledSide, ['internal','external'], true)) $controlledSide = 'internal';

        $mapPlayers = fn($team) => $team->contracts
            ->values()
            ->map(function ($c, $idx) use ($team) {
                $p = $c->gamePlayer;

                return [
                    'id'        => $p->id,
                    'number'    => $idx + 1,
                    'firstname' => $p->firstname,
                    'lastname'  => $p->lastname,
                    'position'  => $p->position,
                    'stats' => [
                        'speed'      => $p->speed,
                        'stamina'    => $p->stamina,
                        'attack'     => $p->attack,
                        'defense'    => $p->defense,
                        'shot'       => $p->shot,
                        'pass'       => $p->pass,
                        'dribble'    => $p->dribble,
                        'block'      => $p->block,
                        'intercept'  => $p->intercept,
                        'tackle'     => $p->tackle,
                        'hand_save'  => $p->hand_save,
                        'punch_save' => $p->punch_save,
                    ],
                ];
            })->values();

        return Inertia::render('Match/Engine', [
            'engineConfig' => [
                'gameSaveId'     => $gameSave->id,
                'matchId'        => $match->id,
                'week'           => $match->week,
                'maxTurns'       => 30,
                'controlMode'    => $controlMode,
                'controlledSide' => $controlledSide, // toujours internal|external
                'teams' => [
                    'internal' => [
                        'id'      => $internalTeam->id,
                        'name'    => $internalTeam->name,
                        'players' => $mapPlayers($internalTeam),
                    ],
                    'external' => [
                        'id'      => $externalTeam->id,
                        'name'    => $externalTeam->name,
                        'players' => $mapPlayers($externalTeam),
                    ],
                ],
            ],
        ]);
    }

    public function continue(Request $request): RedirectResponse
    {
        $lastSave = GameSave::where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->first();

        if (! $lastSave) {
            return redirect()
                ->route('game-saves.create')
                ->with('info', 'Aucune partie existante, crée une nouvelle partie.');
        }

        // 👉 renvoie bien vers le DASHBOARD, pas le match
        return redirect()->route('game-saves.play', $lastSave);
    }


    public function update(GameSaveRequest $request, GameSave $gameSave)
    {
        $this->authorizeSave($request, $gameSave);

        $data = $request->validated();

        $gameSave->fill($data);
        $gameSave->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Sauvegarde mise à jour.');
    }

    public function destroy(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeSave($request, $gameSave);

        $gameSave->delete();

        return redirect()
            ->route('game-saves.index')
            ->with('success', 'Sauvegarde supprimée.');
    }

    private function authorizeSave(Request $request, GameSave $gameSave): void
    {
        if ($gameSave->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    public function finishMatch(Request $request, GameSave $gameSave, GameMatch $match): RedirectResponse
    {
        $this->authorizeSave($request, $gameSave);

        abort_unless($match->game_save_id === $gameSave->id, 404);

        $data = $request->validate([
            'home_score' => ['required', 'integer', 'min:0'],
            'away_score' => ['required', 'integer', 'min:0'],
        ]);

        // déjà joué ? (sécurité)
        if ($match->status === 'played') {
            return redirect()->route('game-saves.play', $gameSave);
        }

        // 1) sauver le match
        $match->update([
            'home_score' => $data['home_score'],
            'away_score' => $data['away_score'],
            'status'     => 'played',
        ]);

        // 2) MAJ classement (wins/draws/losses)
        $home = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($match->home_team_id);
        $away = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($match->away_team_id);

        if ($data['home_score'] > $data['away_score']) {
            $home->wins++;  $away->losses++;
        } elseif ($data['home_score'] < $data['away_score']) {
            $away->wins++;  $home->losses++;
        } else {
            $home->draws++; $away->draws++;
        }

        $home->save();
        $away->save();
        app(MatchSimulator::class)->simulateOtherMatchesOfWeek($match);

        // (optionnel mais conseillé) avancer la semaine du save
        $gameSave->week = max($gameSave->week ?? 1, $match->week + 1);
        $gameSave->save();

        // 3) retour dashboard (props à jour)
        return redirect()->route('game-saves.play', $gameSave);
    }

}

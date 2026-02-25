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
use Illuminate\Support\Facades\Storage;


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
                'logo_path'    => $team->logo_path,
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
                'description'    => $player->description,
                'photo_path'     => $player->photo_path,

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

                'special_moves' => $player->special_moves ?? [],

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

        $currentWeek = $gameSave->week ?? 1;

        $hasMatchThisWeek = GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', $currentWeek)
            ->where(function ($q) use ($controlledTeam) {
                $q->where('home_team_id', $controlledTeam->id)
                    ->orWhere('away_team_id', $controlledTeam->id);
            })
            ->exists();


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
            'hasMatchThisWeek'=> $hasMatchThisWeek,
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

        // ✅ IMPORTANT: on ne swap plus internal/external.
        // internal = home (DB), external = away (DB)
        $internalTeam = $homeTeam;
        $externalTeam = $awayTeam;

        // Déterminer si l'équipe contrôlée est home ou away (DB)
        $isControlledHome = ((int) $controlledGameTeam->id === (int) $homeTeam->id);

        $controlMode = $request->query('controlMode', 'both'); // both|single
        if (!in_array($controlMode, ['both', 'single'], true)) {
            $controlMode = 'both';
        }

        // ✅ controlledSide par défaut = côté réel en DB
        $controlledSide = $request->query('controlledSide', $isControlledHome ? 'internal' : 'external');
        if (!in_array($controlledSide, ['internal', 'external'], true)) {
            $controlledSide = $isControlledHome ? 'internal' : 'external';
        }

        $mapPlayers = fn($team) => $team->contracts
            ->values()
            ->map(function ($c, $idx) {
                $p = $c->gamePlayer;

                // ✅ URL publique de la photo (si stockée dans storage/app/public)
                $photoUrl = $p->photo_path ? Storage::url($p->photo_path) : null;

                return [
                    'id'        => $p->id,
                    'number'    => $idx + 1,
                    'firstname' => $p->firstname,
                    'lastname'  => $p->lastname,
                    'position'  => $p->position,

                    // ✅ AJOUT: champs photo pour le front
                    'photo_path' => $p->photo_path,
                    'photo_url'  => $photoUrl,

                    'stats'     => [
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
                    'special_moves' => $p->special_moves ?? [],
                ];
            })->values();

        // ✅ AJOUT: URLs prêtes pour la vue (tailwind <img :src="...">)
        // logo_path = "images/teams/xxx.webp" => url = "/images/teams/xxx.webp"
        $homeLogoUrl = $homeTeam->logo_path ? '/' . ltrim($homeTeam->logo_path, '/') : null;
        $awayLogoUrl = $awayTeam->logo_path ? '/' . ltrim($awayTeam->logo_path, '/') : null;

        return Inertia::render('Match/Engine', [
            'engineConfig' => [
                'gameSaveId'     => $gameSave->id,
                'matchId'        => $match->id,
                'week'           => $match->week,
                'maxTurns'       => 30,
                'controlMode'    => $controlMode,
                'controlledSide' => $controlledSide, // internal|external

                // ✅ AJOUT: champs simples pour le score-strip (nom + logo)
                'homeTeamName'   => $homeTeam->name,
                'awayTeamName'   => $awayTeam->name,
                'homeLogoUrl'    => $homeLogoUrl,
                'awayLogoUrl'    => $awayLogoUrl,

                // vérité DB
                'homeTeamId'     => $match->home_team_id,
                'awayTeamId'     => $match->away_team_id,

                // mapping UI → DB (maintenu)
                'sides' => [
                    'internalTeamId' => $internalTeam->id, // == home_team_id
                    'externalTeamId' => $externalTeam->id, // == away_team_id
                ],

                'teams' => [
                    'internal' => [
                        'id'        => $internalTeam->id,
                        'name'      => $internalTeam->name,
                        'logo_path' => $internalTeam->logo_path,
                        'players'   => $mapPlayers($internalTeam),
                    ],
                    'external' => [
                        'id'        => $externalTeam->id,
                        'name'      => $externalTeam->name,
                        'logo_path' => $externalTeam->logo_path,
                        'players'   => $mapPlayers($externalTeam),
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

        // Simule tous les matchs jouables de la semaine
        app(MatchSimulator::class)->simulateMatchesCollection($matches);

        // Avance la semaine
        $gameSave->week = $week + 1;
        $gameSave->save();

        return redirect()->route('game-saves.play', $gameSave);
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

    public function finishMatch(
        Request $request,
        GameSave $gameSave,
        GameMatch $match
    ): RedirectResponse
    {
        $this->authorizeSave($request, $gameSave);

        abort_unless((int) $match->game_save_id === (int) $gameSave->id, 404);

        // Sécurité : match déjà joué
        if ($match->status === 'played') {
            return redirect()->route('game-saves.play', $gameSave);
        }

        $data = $request->validate([
            'scoresByTeamId' => ['required', 'array', 'min:2'],
            'scoresByTeamId.*' => ['integer', 'min:0'],
            'playerActions' => ['array'],
        ]);

        $scores = $data['scoresByTeamId'];

        // IDs DB
        $homeTeamId = (int) $match->home_team_id;
        $awayTeamId = (int) $match->away_team_id;

        if (!array_key_exists($homeTeamId, $scores) || !array_key_exists($awayTeamId, $scores)) {
            abort(422, 'Scores incomplets pour ce match.');
        }

        $homeScore = (int) $scores[$homeTeamId];
        $awayScore = (int) $scores[$awayTeamId];

        // 1️⃣ Sauvegarde du match (DB = vérité)
        $match->update([
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'status'     => 'played',
        ]);

        // 2️⃣ Mise à jour classement
        $home = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($homeTeamId);
        $away = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($awayTeamId);

        if ($homeScore > $awayScore) {
            $home->wins++;
            $away->losses++;
        } elseif ($homeScore < $awayScore) {
            $away->wins++;
            $home->losses++;
        } else {
            $home->draws++;
            $away->draws++;
        }

        $home->save();
        $away->save();

        // 3️⃣ Simulation autres matchs de la semaine
        app(MatchSimulator::class)->simulateOtherMatchesOfWeek($match);

        // 4️⃣ Avancer la semaine
        $gameSave->week = max($gameSave->week ?? 1, $match->week + 1);
        $gameSave->save();

        $state = $gameSave->state ?? [];

        $existing = $state['player_actions'] ?? [];
        $new      = $data['playerActions'] ?? [];

        // On concatène
        $state['player_actions'] = array_merge($existing, $new);

        $gameSave->state = $state;
        $gameSave->save();
        $this->accumulatePlayerSeasonStats($gameSave, $new);
        return redirect()->route('game-saves.play', $gameSave);
    }

    private function accumulatePlayerSeasonStats(GameSave $gameSave, array $newActions): void
    {
        $state = $gameSave->state ?? [];
        $stats = $state['player_stats'] ?? [];

        foreach ($newActions as $ev) {
            // Attaque
            if (!empty($ev['attack']['game_player_id'])) {
                $pid = $ev['attack']['game_player_id'];
                $action = $ev['attack']['action']; // "pass" | "shot" | "dribble" | "special"
                $result = $ev['result'] ?? null;

                $stats[$pid] = $stats[$pid] ?? $this->emptyPlayerStats();

                if (isset($stats[$pid]['offense'][$action])) {
                    $stats[$pid]['offense'][$action]['attempts']++;
                    if ($result === 'attack') {
                        $stats[$pid]['offense'][$action]['success']++;
                    }
                }

                if ($result === 'attack') {
                    $stats[$pid]['duelsWon']++;
                } elseif ($result === 'defense') {
                    $stats[$pid]['duelsLost']++;
                }
            }

            // Défense
            if (!empty($ev['defense']['game_player_id'])) {
                $pid = $ev['defense']['game_player_id'];
                $defAction = $ev['defense']['action']; // ex: "intercept","tackle","block","hands","punch","gk-special"
                $result = $ev['result'] ?? null;

                $stats[$pid] = $stats[$pid] ?? $this->emptyPlayerStats();

                $map = [
                    'intercept'  => 'intercept',
                    'tackle'     => 'tackle',
                    'block'      => 'block',
                    'hands'      => 'hands',
                    'punch'      => 'punch',
                    'gk-special' => 'gkSpecial',
                ];

                if (isset($map[$defAction])) {
                    $key = $map[$defAction];
                    $stats[$pid]['defense'][$key]['attempts']++;
                    if ($result === 'defense') {
                        $stats[$pid]['defense'][$key]['success']++;
                    }
                }

                if ($result === 'defense') {
                    $stats[$pid]['duelsWon']++;
                } elseif ($result === 'attack') {
                    $stats[$pid]['duelsLost']++;
                }
            }
        }

        $state['player_stats'] = $stats;
        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Initialise la structure de stats pour un joueur.
     */
    private function emptyPlayerStats(): array
    {
        return [
            'offense' => [
                'pass'    => ['attempts' => 0, 'success' => 0],
                'shot'    => ['attempts' => 0, 'success' => 0],
                'dribble' => ['attempts' => 0, 'success' => 0],
                'special' => ['attempts' => 0, 'success' => 0],
            ],
            'defense' => [
                'intercept' => ['attempts' => 0, 'success' => 0],
                'tackle'    => ['attempts' => 0, 'success' => 0],
                'block'     => ['attempts' => 0, 'success' => 0],
                'hands'     => ['attempts' => 0, 'success' => 0],
                'punch'     => ['attempts' => 0, 'success' => 0],
                'gkSpecial' => ['attempts' => 0, 'success' => 0],
            ],
            'duelsWon'  => 0,
            'duelsLost' => 0,
        ];
    }
}

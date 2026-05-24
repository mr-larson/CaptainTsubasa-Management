<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Requests\GameSaves\GameSaveRequest;
use App\Models\Contract;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameTeam;
use App\Models\Player;
use App\Models\Team;
use App\Services\PlayerStatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GameSaveController extends Controller
{
    public function index(Request $request): Response
    {
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
     * Étape 1 : reçoit label + period → affiche choix d'équipe.
     */
    public function store(Request $request): Response
    {
        $data = $request->validate([
            'label'  => ['nullable', 'string', 'max:255'],
            'period' => ['required', 'string', 'in:college'],
        ]);

        $teams = Team::with(['contracts.player'])->orderBy('name')->get();

        return Inertia::render('GameSaves/TeamSelection', [
            'label'  => $data['label'] ?? null,
            'period' => $data['period'],
            'teams'  => $teams,
        ]);
    }

    /**
     * Étape 2 : crée la sauvegarde et duplique équipes/joueurs/contrats.
     */
    public function start(GameSaveRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $gameSave = GameSave::create([
            'user_id' => $request->user()->id,
            'team_id' => $data['team_id'],
            'period'  => $data['period'],
            'season'  => 1,
            'week'    => 1,
            'label'   => $data['label'] ?? null,
            'state'   => null,
        ]);

        // 1. Dupliquer les équipes
        $teams             = Team::orderBy('id')->get();
        $teamCount         = $teams->count();
        $seasonLength      = max(1, ($teamCount - 1) * 2);
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

        // 2. Dupliquer les joueurs
        $players             = Player::orderBy('id')->get();
        $gamePlayersByBaseId = [];

        foreach ($players as $player) {
            $s = $player->stats ?? [];
            $gamePlayer = GamePlayer::create([
                'game_save_id'   => $gameSave->id,
                'base_player_id' => $player->id,
                'firstname'      => $player->firstname,
                'lastname'       => $player->lastname,
                'position'       => $player->position,
                'description'    => $player->description,
                'photo_path'     => $player->photo_path,
                'speed'          => $player->speed      ?? $s['speed']      ?? 50,
                'stamina'        => $player->stamina    ?? $s['stamina']    ?? 50,
                'attack'         => $player->attack     ?? $s['attack']     ?? 50,
                'defense'        => $player->defense    ?? $s['defense']    ?? 50,
                'shot'           => $player->shot       ?? $s['shot']       ?? 50,
                'pass'           => $player->pass       ?? $s['pass']       ?? 50,
                'dribble'        => $player->dribble    ?? $s['dribble']    ?? 50,
                'block'          => $player->block      ?? $s['block']      ?? 50,
                'intercept'      => $player->intercept  ?? $s['intercept']  ?? 50,
                'tackle'         => $player->tackle     ?? $s['tackle']     ?? 50,
                'hand_save'      => $player->hand_save  ?? $s['hand_save']  ?? 0,
                'punch_save'     => $player->punch_save ?? $s['punch_save'] ?? 0,
                'special_moves'  => $player->special_moves ?? [],
                'cost'           => $player->cost ?? 0,
            ]);
            $gamePlayersByBaseId[$player->id] = $gamePlayer;
        }

        // 3. Dupliquer les contrats
        $contracts       = Contract::with(['team', 'player'])->orderBy('id')->get();
        $contractsByTeam = $contracts->groupBy('team_id');

        foreach ($contractsByTeam as $teamId => $teamContracts) {
            if (!isset($gameTeamsByBaseId[$teamId])) continue;

            $gameTeamId   = $gameTeamsByBaseId[$teamId]->id;
            $teamContracts = $teamContracts->values();

            foreach ($teamContracts as $index => $contract) {
                $basePlayerId = $contract->player->id;
                if (!isset($gamePlayersByBaseId[$basePlayerId])) continue;

                GameContract::create([
                    'game_save_id'   => $gameSave->id,
                    'game_team_id'   => $gameTeamId,
                    'game_player_id' => $gamePlayersByBaseId[$basePlayerId]->id,
                    'salary'         => $contract->salary ?? 0,
                    'start_week'     => 1,
                    'end_week'       => $seasonLength,
                    'is_starter'     => $index < 11,
                ]);
            }
        }

        return redirect()->route('game-saves.play', $gameSave)->with('success', 'Partie créée');
    }

    /**
     * Dashboard principal de la session.
     * Agrège les stats saison depuis game_matches.match_stats (source DB).
     */
    public function play(Request $request, GameSave $gameSave): Response
    {
        $this->authorizeSave($request, $gameSave);

        $gameTeams = GameTeam::with(['contracts.gamePlayer'])
            ->where('game_save_id', $gameSave->id)
            ->orderBy('name')
            ->get();

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

        // ─── Stats saison agrégées depuis game_matches.match_stats ───
        $playerSeasonStats = PlayerStatsService::aggregateForSave($gameSave);

        // ─── Blessures et suspensions actives ───
        $currentWeek = $gameSave->week ?? 1;

        $activeInjuries = GameInjury::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $currentWeek)
            ->with('gamePlayer')
            ->get()
            ->map(fn($i) => [
                'game_player_id' => $i->game_player_id,
                'severity'       => $i->severity,
                'weeks_out'      => $i->weeks_out,
                'week_return'    => $i->week_return,
                'description'    => $i->description,
            ]);

        $activeSuspensions = GameSanction::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $currentWeek)
            ->where('weeks_suspended', '>', 0)
            ->get()
            ->map(fn($s) => [
                'game_player_id'  => $s->game_player_id,
                'type'            => $s->type,
                'weeks_suspended' => $s->weeks_suspended,
                'week_return'     => $s->week_return,
            ]);

        $activeYellowCards = GameSanction::where('game_save_id', $gameSave->id)
            ->where('type', 'yellow')
            ->where('week_match', '>=', $currentWeek - 20)
            ->get()
            ->groupBy('game_player_id')
            ->map(fn($cards) => $cards->count());

        return Inertia::render('GameSaves/Play', [
            'gameSave'          => $gameSave,
            'teams'             => $gameTeams,
            'matches'           => $matches,
            'freePlayers'       => $freePlayers,
            'controlledTeam'    => $controlledTeam,
            'playerSeasonStats' => $playerSeasonStats,
            'activeInjuries'    => $activeInjuries,
            'activeSuspensions' => $activeSuspensions,
            'activeYellowCards' => $activeYellowCards,
        ]);
    }

    public function signFreeAgent(Request $request, GameSave $gameSave, GamePlayer $player): RedirectResponse
    {
        $this->authorizeSave($request, $gameSave);

        $data = $request->validate([
            'team_id'       => ['required', 'integer', Rule::exists('game_teams', 'id')->where('game_save_id', $gameSave->id)],
            'salary'        => ['required', 'integer', 'min:0'],
            'matches_total' => ['required', 'integer', 'min:1'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ]);

        $team = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($data['team_id']);

        $alreadyHasContract = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->exists();

        if ($alreadyHasContract) {
            return back()->with('info', 'Ce joueur a déjà un contrat en cours dans cette partie.');
        }

        $startWeek    = $gameSave->week ?? 1;
        $endWeek      = $startWeek + $data['matches_total'] - 1;
        $starterCount = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $team->id)
            ->where('is_starter', true)
            ->count();

        GameContract::create([
            'game_save_id'   => $gameSave->id,
            'game_team_id'   => $team->id,
            'game_player_id' => $player->id,
            'salary'         => $data['salary'],
            'start_week'     => $startWeek,
            'end_week'       => $endWeek,
            'is_starter'     => $starterCount < 11,
        ]);

        $totalCost    = $data['salary'] * $data['matches_total'];
        $team->budget = max(0, ($team->budget ?? 0) - $totalCost);
        $team->save();

        $state = $gameSave->state ?? [];
        $state['free_agent_signings'][] = [
            'player_id'     => $player->id,
            'team_id'       => $team->id,
            'salary'        => $data['salary'],
            'matches_total' => $data['matches_total'],
            'total_cost'    => $totalCost,
            'reason'        => $data['reason'] ?? null,
            'week'          => $gameSave->week,
        ];
        $gameSave->state = $state;
        $gameSave->save();

        return back()->with('success', 'Joueur signé et budget mis à jour.');
    }

    public function update(GameSaveRequest $request, GameSave $gameSave): mixed
    {
        $this->authorizeSave($request, $gameSave);
        $gameSave->fill($request->validated())->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Sauvegarde mise à jour.');
    }

    public function destroy(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeSave($request, $gameSave);
        $gameSave->delete();

        return redirect()->route('game-saves.index')->with('success', 'Sauvegarde supprimée.');
    }

    public function continue(Request $request): RedirectResponse
    {
        $lastSave = GameSave::where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->first();

        if (!$lastSave) {
            return redirect()->route('game-saves.create')->with('info', 'Aucune partie existante.');
        }

        return redirect()->route('game-saves.play', $lastSave);
    }

    // ==========================
    //   HELPERS PRIVÉS
    // ==========================

    private function ensureCalendar(GameSave $gameSave, Collection $teams): void
    {
        if ($gameSave->matches()->exists()) return;

        $teamIds   = $teams->pluck('id')->values()->all();
        $teamCount = count($teamIds);
        if ($teamCount < 2) return;

        if ($teamCount % 2 === 1) {
            $teamIds[] = null;
            $teamCount++;
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
                if ($home === null || $away === null) continue;

                GameMatch::create(['game_save_id' => $gameSave->id, 'week' => $weekAller,  'home_team_id' => $home, 'away_team_id' => $away, 'status' => 'scheduled']);
                GameMatch::create(['game_save_id' => $gameSave->id, 'week' => $weekRetour, 'home_team_id' => $away, 'away_team_id' => $home, 'status' => 'scheduled']);
            }

            $fixed = array_shift($ids);
            $last  = array_pop($ids);
            array_unshift($ids, $fixed);
            array_splice($ids, 1, 0, [$last]);
        }
    }

    private function authorizeSave(Request $request, GameSave $gameSave): void
    {
        if ($gameSave->user_id !== $request->user()->id) abort(403);
    }
}

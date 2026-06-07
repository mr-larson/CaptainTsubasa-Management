<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Requests\GameSaves\GameSaveRequest;
use App\Models\Contract;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameTeam;
use App\Models\Player;
use App\Models\Team;
use App\Services\BonusCardShopService;
use App\Services\PlayerStatsService;
use App\Services\DraftService;
use App\Services\DraftAIService;
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
            'label'     => ['nullable', 'string', 'max:255'],
            'period'    => ['required', 'string', 'in:college'],
            'game_mode' => ['nullable', 'string', 'in:prebuilt,draft'],
        ]);

        $teams = Team::with(['contracts.player'])->orderBy('name')->get();

        return Inertia::render('GameSaves/TeamSelection', [
            'label'  => $data['label'] ?? null,
            'period' => $data['period'],
            'teams'  => $teams,
            'gameMode' => $data['game_mode'] ?? 'prebuilt',
        ]);
    }

    /**
     * Étape 2 : crée la sauvegarde et duplique équipes/joueurs/contrats.
     */
    public function draft(Request $request, GameSave $gameSave): Response
    {
        $this->authorizeSave($request, $gameSave);

        if ($gameSave->phase !== 'draft') {
            return redirect()->route('game-saves.play', $gameSave);
        }

        $gameTeams = GameTeam::with(['contracts.gamePlayer'])
            ->where('game_save_id', $gameSave->id)
            ->orderBy('name')
            ->get();

        $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts')
            ->orderBy('lastname')
            ->get();

        return Inertia::render('GameSaves/Draft', [
            'gameSave'     => $gameSave,
            'teams'        => $gameTeams,
            'freePlayers'  => $freePlayers,
            'draftState'   => $gameSave->state['draft'] ?? null,
            'controlledTeamId' => $gameSave->controlled_game_team_id,
        ]);
    }

    /**
     * Exécute un pick IA (appelé par le frontend pour chaque tour IA).
     */
    public function draftAiPick(Request $request, GameSave $gameSave)
    {
        $this->authorizeSave($request, $gameSave);

        if ($gameSave->phase !== 'draft') {
            return response()->json(['error' => 'Not in draft phase'], 400);
        }

        $draftService = app(DraftService::class);

        // Vérifier que ce n'est PAS le tour du joueur humain
        if ($draftService->isHumanTurn($gameSave)) {
            return response()->json(['error' => 'It is your turn to pick'], 400);
        }

        $draft = ($gameSave->state ?? [])['draft'] ?? null;
        if (!$draft || ($draft['completed'] ?? false)) {
            // Draft terminé → finaliser
            $draftService->finalizeDraft($gameSave);
            return response()->json(['completed' => true]);
        }

        // L'IA choisit un joueur
        $aiService     = app(DraftAIService::class);
        $currentTeamId = $draftService->getCurrentTeamId($gameSave);
        $team          = GameTeam::find($currentTeamId);

        if (!$team) {
            return response()->json(['error' => 'Team not found'], 400);
        }

        // Vérifier si l'IA veut arrêter
        $squadSize = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $team->id)
            ->count();

        if ($aiService->shouldFinishDraft($team, $squadSize, $team->budget ?? 0)) {
            $draftService->finishTeamDraft($gameSave, $team->id);
            $gameSave->refresh();
            $draftState = $gameSave->state['draft'] ?? [];

            return response()->json([
                'pick'       => null,
                'finished'   => true,
                'team_name'  => $team->name,
                'draftState' => $draftState,
                'completed'  => $draftState['completed'] ?? false,
            ]);
        }

        $playerId = $aiService->chooseBestPlayer($gameSave, $team);

        if (!$playerId) {
            // Aucun joueur trouvable → cette équipe a fini (budget insuffisant)
            $draftService->finishTeamDraft($gameSave, $team->id);
            $gameSave->refresh();
            $draftState = $gameSave->state['draft'] ?? [];

            return response()->json([
                'pick'       => null,
                'finished'   => true,
                'team_name'  => $team->name,
                'draftState' => $draftState,
                'completed'  => $draftState['completed'] ?? false,
            ]);
        }

        $pick = $draftService->executePick($gameSave, $playerId);
        $gameSave->refresh();

        $draftState = $gameSave->state['draft'] ?? [];

        if ($draftState['completed'] ?? false) {
            $draftService->finalizeDraft($gameSave);
        }

        return response()->json([
            'pick'       => $pick,
            'draftState' => $draftState,
            'completed'  => $draftState['completed'] ?? false,
        ]);
    }

    /**
     * Le joueur humain pioche un joueur.
     */
    public function draftPick(Request $request, GameSave $gameSave)
    {
        $this->authorizeSave($request, $gameSave);

        if ($gameSave->phase !== 'draft') {
            return response()->json(['error' => 'Not in draft phase'], 400);
        }

        $data = $request->validate([
            'player_id' => ['required', 'integer'],
        ]);

        $draftService = app(DraftService::class);

        if (!$draftService->isHumanTurn($gameSave)) {
            return response()->json(['error' => 'Not your turn'], 400);
        }

        $pick = $draftService->executePick($gameSave, $data['player_id']);

        if (!$pick) {
            return response()->json(['error' => 'Invalid pick (player taken, too expensive, or squad full)'], 422);
        }

        $gameSave->refresh();
        $draftState = $gameSave->state['draft'] ?? [];

        if ($draftState['completed'] ?? false) {
            $draftService->finalizeDraft($gameSave);
        }

        return response()->json([
            'pick'       => $pick,
            'draftState' => $draftState,
            'completed'  => $draftState['completed'] ?? false,
        ]);
    }

    public function start(GameSaveRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $gameMode = $data['game_mode'] ?? 'prebuilt';
        $isDraft  = $gameMode === 'draft';

        $gameSave = GameSave::create([
            'user_id' => $request->user()->id,
            'team_id' => $data['team_id'],
            'period'  => $data['period'],
            'season'  => 1,
            'week'    => 1,
            'phase'   => $isDraft ? 'draft' : 'season',
            'label'   => $data['label'] ?? null,
            'state'   => null,
        ]);

        // 1. Dupliquer les équipes
        $teams             = Team::orderBy('id')->get();
        $teamCount         = $teams->count();
        $seasonLength      = max(1, ($teamCount - 1) * 2);
        $gameTeamsByBaseId = [];

        $formationByTeam = [
            'Nankatsu'  => '4-1-3-2',
            'Toho'      => '4-2-2-2',
            'Hanawa'    => '3-2-2-3',
            'Furano'    => '3-2-3-2',
            'Otomo'     => '4-2-2-2',
            'Azumaichi' => '3-2-2-3',
            'Musashi'   => '3-1-3-3',
            'Shutetsu'  => '4-2-2-2',
            'Meiwa'     => '4-3-1-2',
            'Hirado'    => '5-2-2-1',
            'Naniwa'    => '4-3-1-2',
            'Minawi'    => '4-2-2-2',
            'Nakahara'  => '3-3-2-2',
            'Shimizu'   => '5-1-2-2',
            'Shimada'   => '5-3-1-1',
        ];

        foreach ($teams as $team) {
            $gameTeam = GameTeam::create([
                'game_save_id'          => $gameSave->id,
                'base_team_id'          => $team->id,
                'name'                  => $team->name,
                'description'           => $team->description,
                'budget'                => $team->budget,
                'wins'                  => 0,
                'draws'                 => 0,
                'losses'                => 0,
                'logo_path'             => $team->logo_path,
                'formation'             => $formationByTeam[$team->name] ?? '3-2-3-2',
                'tactical_style'        => $team->tactical_style        ?? 'balanced',
                'management_philosophy' => $team->management_philosophy ?? 'collective',
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
                'stamina'        => rand(60, 100),
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

        // 3. Dupliquer les contrats (mode prebuilt uniquement)
        if (!$isDraft) {
            $contracts       = Contract::with(['team', 'player'])->orderBy('id')->get();
            $contractsByTeam = $contracts->groupBy('team_id');

            foreach ($contractsByTeam as $teamId => $teamContracts) {
                if (!isset($gameTeamsByBaseId[$teamId])) continue;
                $gameTeamId    = $gameTeamsByBaseId[$teamId]->id;
                $teamContracts = $teamContracts->values();

                $starterNumber = 1;
                $subNumber     = 12;

                foreach ($teamContracts as $index => $contract) {
                    $basePlayerId = $contract->player->id;
                    if (!isset($gamePlayersByBaseId[$basePlayerId])) continue;

                    $isStarter    = $index < 11;
                    $jerseyNumber = $isStarter ? $starterNumber++ : $subNumber++;

                    $gamePlayersByBaseId[$basePlayerId]->number = $jerseyNumber;
                    $gamePlayersByBaseId[$basePlayerId]->save();

                    GameContract::create([
                        'game_save_id'                    => $gameSave->id,
                        'game_team_id'                    => $gameTeamId,
                        'game_player_id'                  => $gamePlayersByBaseId[$basePlayerId]->id,
                        'salary'                          => $contract->salary ?? 0,
                        'start_week'                      => 1,
                        'end_week'                        => $seasonLength,
                        'is_starter'                      => $isStarter,
                        'is_captain'                      => $contract->is_captain ?? false,
                        'captain_rerolls_remaining'       => 3,
                        'captain_reroll_used_this_action' => false,
                    ]);
                }
            }
        }

        // 4. Mode draft : bonus budget + init draft state
        if ($isDraft) {
            $draftBonus = 5000;
            foreach ($gameTeamsByBaseId as $gameTeam) {
                $gameTeam->budget = ($gameTeam->budget ?? 0) + $draftBonus;
                $gameTeam->save();
            }

            $teamIds = array_values(array_map(fn($t) => $t->id, $gameTeamsByBaseId));
            shuffle($teamIds);

            $state = $gameSave->state ?? [];
            $state['draft'] = [
                'order'              => $teamIds,
                'current_pick_index' => 0,
                'round'              => 1,
                'picks'              => [],
                'completed'          => false,
            ];
            $gameSave->state = $state;
            $gameSave->save();

            return redirect()->route('game-saves.draft', $gameSave)
                ->with('success', 'Partie créée — le draft commence !');
        }

        return redirect()->route('game-saves.play', $gameSave)->with('success', 'Partie créée');
    }

    public function draftFinish(Request $request, GameSave $gameSave)
    {
        $this->authorizeSave($request, $gameSave);

        $draftService = app(DraftService::class);
        $allDone = $draftService->finishTeamDraft($gameSave, $gameSave->controlled_game_team_id);

        $gameSave->refresh();

        return response()->json([
            'draftState' => $gameSave->state['draft'] ?? [],
            'completed'  => $allDone || ($gameSave->state['draft']['completed'] ?? false),
        ]);
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

        // ─── Bonus cards ───
        $controlledTeamId = $gameSave->controlled_game_team_id;

        $bonusCardOffers = $controlledTeamId
            ? app(BonusCardShopService::class)->getOffersForTeam($gameSave, $controlledTeamId)
            : [];

        $bonusCardInventory = $controlledTeamId
            ? GameBonusCard::where('game_save_id', $gameSave->id)
                ->where('game_team_id', $controlledTeamId)
                ->with('bonusCard')
                ->orderByDesc('purchased_week')
                ->get()
                ->map(fn($c) => [
                    'id'               => $c->id,
                    'bonus_card_id'    => $c->bonus_card_id,
                    'status'           => $c->status,
                    'tier'             => $c->tier,
                    'cost_paid'        => $c->cost_paid,
                    'purchased_week'   => $c->purchased_week,
                    'purchased_season' => $c->purchased_season,
                    'used_week'        => $c->used_week,
                    'used_season'      => $c->used_season,
                    'name'             => $c->bonusCard->name,
                    'description'      => $c->bonusCard->description,
                    'icon'             => $c->bonusCard->icon,
                    'target'           => $c->bonusCard->target,
                    'execution_phase'  => $c->bonusCard->execution_phase,
                    'effect_type'      => $c->bonusCard->effect_type,
                    'effect_value'     => $c->bonusCard->effect_value,
                ])
            : collect();

        return Inertia::render('GameSaves/Play', [
            'gameSave'            => $gameSave,
            'teams'               => $gameTeams,
            'matches'             => $matches,
            'freePlayers'         => $freePlayers,
            'controlledTeam'      => $controlledTeam,
            'playerSeasonStats'   => $playerSeasonStats,
            'activeInjuries'      => $activeInjuries,
            'activeSuspensions'   => $activeSuspensions,
            'activeYellowCards'   => $activeYellowCards,
            'bonusCardOffers'     => $bonusCardOffers,
            'bonusCardInventory'  => $bonusCardInventory,
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

        // Premier numéro disponible dans l'équipe
        $usedNumbers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereHas('contracts', fn($q) => $q->where('game_team_id', $team->id))
            ->pluck('number')
            ->filter()
            ->toArray();

        $nextNumber = 1;
        while (in_array($nextNumber, $usedNumbers)) {
            $nextNumber++;
        }

        $player->number = $nextNumber;
        $player->save();

        GameContract::create([
            'game_save_id'                    => $gameSave->id,
            'game_team_id'                    => $team->id,
            'game_player_id'                  => $player->id,
            'salary'                          => $data['salary'],
            'start_week'                      => $startWeek,
            'end_week'                        => $endWeek,
            'is_starter'                      => $starterCount < 11,
            'is_captain'                      => false,
            'captain_rerolls_remaining'       => 3,
            'captain_reroll_used_this_action' => false,
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

        // Mélanger pour éviter que la même équipe soit toujours extérieure
        shuffle($teamIds);

        if ($teamCount % 2 === 1) {
            $teamIds[] = null;
            $teamCount++;
        }

        $rounds = $teamCount - 1;
        $half   = $teamCount / 2;
        $ids    = $teamIds;
        $matches = [];

        for ($round = 0; $round < $rounds; $round++) {
            $weekAller  = $round + 1;
            $weekRetour = $round + 1 + $rounds;

            for ($i = 0; $i < $half; $i++) {
                $home = $ids[$i];
                $away = $ids[$teamCount - 1 - $i];
                if ($home === null || $away === null) continue;

                // Alterner qui reçoit à l'aller vs retour
                if ($round % 2 === 0) {
                    $matches[] = ['week' => $weekAller,  'home' => $home, 'away' => $away];
                    $matches[] = ['week' => $weekRetour, 'home' => $away, 'away' => $home];
                } else {
                    $matches[] = ['week' => $weekAller,  'home' => $away, 'away' => $home];
                    $matches[] = ['week' => $weekRetour, 'home' => $home, 'away' => $away];
                }
            }

            $fixed = array_shift($ids);
            $last  = array_pop($ids);
            array_unshift($ids, $fixed);
            array_splice($ids, 1, 0, [$last]);
        }

        foreach ($matches as $m) {
            GameMatch::create([
                'game_save_id' => $gameSave->id,
                'week'         => $m['week'],
                'home_team_id' => $m['home'],
                'away_team_id' => $m['away'],
                'status'       => 'scheduled',
            ]);
        }
    }

    private function authorizeSave(Request $request, GameSave $gameSave): void
    {
        if ($gameSave->user_id !== $request->user()->id) abort(403);
    }
}

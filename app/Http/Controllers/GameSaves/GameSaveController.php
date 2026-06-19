<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Requests\GameSaves\GameSaveRequest;
use App\Models\Contract;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePlayerMoraleLog;
use App\Models\GameSaves\GameDeclaration;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameTeam;
use App\Models\Player;
use App\Models\Team;
use App\Services\BonusCardShopService;
use App\Services\MoraleService;
use App\Services\PlayerStatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GameSaveController extends Controller
{
    use AuthorizesGameSave;

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

    public function start(GameSaveRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $gameMode = $data['game_mode'] ?? 'prebuilt';
        $isDraft  = $gameMode === 'draft';

        // Équipes humaines, dans l'ordre des sièges. Fallback mono-équipe via team_id.
        $humanTeamIds = array_values(array_unique(array_map(
            'intval',
            $data['team_ids'] ?? array_filter([$data['team_id'] ?? null]),
        )));

        // Équipe propriétaire / active de la save = siège 1.
        $primaryTeamId = $humanTeamIds[0] ?? $data['team_id'];

        $gameSave = GameSave::create([
            'user_id' => $request->user()->id,
            'team_id' => $primaryTeamId,
            'period'  => $data['period'],
            'season'  => 1,
            'week'    => 1,
            'phase'   => $isDraft ? 'draft' : 'season',
            'game_mode' => $gameMode,
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
                'game_save_id'          => $gameSave->id,
                'base_team_id'          => $team->id,
                'name'                  => $team->name,
                'description'           => $team->description,
                'budget'                => $team->budget,
                'wins'                  => 0,
                'draws'                 => 0,
                'losses'                => 0,
                'logo_path'             => $team->logo_path,
                'formation'             => $team->default_formation ?? '3-2-3-2',
                'tactical_style'        => $team->tactical_style        ?? 'balanced',
                'management_philosophy' => $team->management_philosophy ?? 'collective',
            ]);
            $gameTeamsByBaseId[$team->id] = $gameTeam;
        }

        // Marquer chaque équipe humaine (is_controlled + siège dans l'ordre choisi).
        $seat = 1;
        $firstControlled = null;
        foreach ($humanTeamIds as $tid) {
            $gameTeam = $gameTeamsByBaseId[$tid] ?? null;
            if (!$gameTeam) {
                continue;
            }
            $gameTeam->is_controlled = true;
            $gameTeam->human_seat    = $seat++;
            $gameTeam->save();
            $firstControlled ??= $gameTeam;
        }

        // Joueur actif = siège 1.
        if ($firstControlled) {
            $gameSave->controlled_game_team_id = $firstControlled->id;
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
                'secondary_positions' => $player->secondary_positions ?? [],
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
                'heading'        => $player->heading    ?? $s['heading']    ?? 15,
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

        return redirect()->route('game-saves.Play', $gameSave)->with('success', 'Partie créée');
    }

    /**
     * Dashboard principal de la session.
     * Agrège les stats saison depuis game_matches.match_stats (source DB).
     */
    public function play(Request $request, GameSave $gameSave): Response
    {
        $this->authorizeGameSave('view', $gameSave);

        $currentWeek = $gameSave->week ?? 1;

        $gameTeams = GameTeam::with(['contracts' => fn($q) => $q->activeAt($currentWeek)->with('gamePlayer')])
            ->where('game_save_id', $gameSave->id)
            ->orderBy('name')
            ->get();

        $gameSave->load('controlledGameTeam');

        // Équipe affichée = joueur ACTIF (hot-seat), avec repli sur l'équipe
        // propriétaire puis la première équipe.
        $controlledTeam = $gameTeams->firstWhere('id', $gameSave->controlled_game_team_id)
            ?? $gameTeams->firstWhere('base_team_id', $gameSave->team_id)
            ?? $gameTeams->first();

        $this->ensureCalendar($gameSave, $gameTeams);

        $matches = GameMatch::with(['homeTeam', 'awayTeam'])
            ->where('game_save_id', $gameSave->id)
            ->orderBy('week')
            ->get();

        $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts', fn($q) => $q->activeAt($currentWeek))
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        // ─── Récap pour l'onglet Gestion ───
        $expiringContracts = GameContract::where('game_save_id', $gameSave->id)
            ->whereBetween('end_week', [$currentWeek, $currentWeek + 4])
            ->with(['gamePlayer', 'gameTeam'])
            ->orderBy('end_week')
            ->get()
            ->map(fn($c) => [
                'id'          => $c->id,
                'player'      => $c->gamePlayer?->full_name ?? 'Joueur inconnu',
                'team'        => $c->gameTeam?->name ?? 'Équipe inconnue',
                'end_week'    => $c->end_week,
            ]);

        $managementStats = [
            'playersCount'           => GamePlayer::where('game_save_id', $gameSave->id)->count(),
            'teamsCount'             => $gameTeams->count(),
            'activeContractsCount'   => $gameTeams->sum(fn($team) => $team->contracts->count()),
            'freePlayersCount'       => $freePlayers->count(),
            'expiringContracts'      => $expiringContracts,
        ];

        // ─── Stats saison agrégées depuis game_matches.match_stats ───
        $playerSeasonStats = PlayerStatsService::aggregateForSave($gameSave);

        // ─── Blessures et suspensions actives ───

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

        // ─── Récap de la semaine écoulée (blessures et suspensions survenues) ───
        $previousWeek = $currentWeek - 1;

        $weeklyRecap = collect();

        if ($previousWeek >= 1) {
            $weeklyRecap = $weeklyRecap
                ->merge(
                    GameInjury::where('game_save_id', $gameSave->id)
                        ->where('week_injured', $previousWeek)
                        ->with('gamePlayer')
                        ->get()
                        ->map(fn($i) => [
                            'type'    => 'injury',
                            'player'  => $i->gamePlayer?->full_name ?? 'Joueur inconnu',
                            'message' => "{$i->gamePlayer?->full_name} blessé " . ($i->weeks_out > 1 ? "{$i->weeks_out} semaines" : "1 semaine"),
                        ])
                )
                ->merge(
                    GameSanction::where('game_save_id', $gameSave->id)
                        ->whereIn('type', ['red', 'double_yellow'])
                        ->where('week_match', $previousWeek)
                        ->with('gamePlayer')
                        ->get()
                        ->map(fn($s) => [
                            'type'    => 'suspension',
                            'player'  => $s->gamePlayer?->full_name ?? 'Joueur inconnu',
                            'message' => "{$s->gamePlayer?->full_name} suspendu " . ($s->weeks_suspended > 1 ? "{$s->weeks_suspended} matchs" : "1 match"),
                        ])
                )
                ->values();
        }

        // ─── Moral : derniers changements par joueur (équipe contrôlée) ───
        $controlledPlayerIds = $controlledTeam
            ? $controlledTeam->contracts->pluck('game_player_id')
            : collect();

        $moraleLogs = GamePlayerMoraleLog::where('game_save_id', $gameSave->id)
            ->whereIn('game_player_id', $controlledPlayerIds)
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->groupBy('game_player_id')
            ->map(fn($logs) => $logs->take(6)->map(fn($l) => [
                'source' => $l->source,
                'value'  => $l->value,
                'label'  => $l->label,
                'week'   => $l->week,
                'season' => $l->season,
            ])->values());

        // ─── Promesses (équipe contrôlée) : en cours + dernières évaluées ───
        $playerPromises = GamePromise::where('game_save_id', $gameSave->id)
            ->whereIn('game_player_id', $controlledPlayerIds)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->groupBy('game_player_id')
            ->map(fn($promises) => $promises->take(2)->map(fn($p) => [
                'id'             => $p->id,
                'type'           => $p->type,
                'start_week'     => $p->start_week,
                'due_week'       => $p->due_week,
                'target_matches' => $p->target_matches,
                'played_matches' => $p->played_matches,
                'status'         => $p->status,
            ])->values());

        // ─── Déclarations publiques (équipe contrôlée) : dernière par joueur ───
        $playerDeclarations = GameDeclaration::where('game_save_id', $gameSave->id)
            ->whereIn('game_player_id', $controlledPlayerIds)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->groupBy('game_player_id')
            ->map(fn($declarations) => $declarations->take(1)->map(fn($d) => [
                'type'           => $d->type,
                'deserved'       => $d->deserved,
                'outcome'        => $d->outcome,
                'affinity_delta' => $d->affinity_delta,
                'morale_delta'   => $d->morale_delta,
                'week'           => $d->week,
                'season'         => $d->season,
            ])->values());

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

        // ─── Hot-seat multi-manager : qui joue, dans quel ordre ───
        $controlledTeams = $gameTeams
            ->whereNotNull('human_seat')
            ->sortBy('human_seat')
            ->values();
        if ($controlledTeams->isEmpty()) {
            $controlledTeams = $gameTeams->where('is_controlled', true)->values();
        }

        $hotSeat = null;
        if ($controlledTeams->count() > 1) {
            $activeId = (int) $gameSave->controlled_game_team_id;
            $hotSeat = [
                'total'   => $controlledTeams->count(),
                'players' => $controlledTeams->values()->map(fn ($t, $i) => [
                    'seat'        => $i + 1,
                    'name'        => $t->name,
                    'game_team_id' => $t->id,
                    'is_active'   => (int) $t->id === $activeId,
                ])->all(),
            ];
        }

        return Inertia::render('GameSaves/Play', [
            'gameSave'            => $gameSave,
            'managementStats'     => $managementStats,
            'teams'               => $gameTeams,
            'matches'             => $matches,
            'freePlayers'         => $freePlayers,
            'controlledTeam'      => $controlledTeam,
            'hotSeat'             => $hotSeat,
            'playerSeasonStats'   => $playerSeasonStats,
            'activeInjuries'      => $activeInjuries,
            'activeSuspensions'   => $activeSuspensions,
            'activeYellowCards'   => $activeYellowCards,
            'weeklyRecap'         => $weeklyRecap,
            'moraleLogs'          => $moraleLogs,
            'playerPromises'      => $playerPromises,
            'playerDeclarations'  => $playerDeclarations,
            'bonusCardOffers'     => $bonusCardOffers,
            'bonusCardInventory'  => $bonusCardInventory,
        ]);
    }

    public function signFreeAgent(Request $request, GameSave $gameSave, GamePlayer $player): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $player);

        $data = $request->validate([
            'team_id'       => ['required', 'integer', Rule::exists('game_teams', 'id')->where('game_save_id', $gameSave->id)],
            'salary'        => ['required', 'integer', 'min:0'],
            'matches_total' => ['required', 'integer', 'min:1'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ]);

        $team = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($data['team_id']);

        $startWeek = $gameSave->week ?? 1;

        $alreadyHasContract = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->activeAt($startWeek)
            ->exists();

        if ($alreadyHasContract) {
            return back()->with('info', 'Ce joueur a déjà un contrat en cours dans cette partie.');
        }

        // Conséquences du moral : un révolté refuse de re-signer avec son ancien club ;
        // signer ailleurs repart sur un moral neutre.
        $lastContract = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_player_id', $player->id)
            ->orderByDesc('end_week')
            ->first();
        $sameTeam = $lastContract && (int) $lastContract->game_team_id === (int) $team->id;

        if ($sameTeam && MoraleService::refusesToSign($player)) {
            return back()->withErrors([
                'contract' => "{$player->full_name} refuse de re-signer avec ce club (moral ou relation avec le coach au plus bas).",
            ]);
        }

        if ($lastContract && !$sameTeam) {
            $player->morale         = MoraleService::NEUTRAL_MORALE;
            $player->coach_affinity = 0;
        }

        $endWeek      = $startWeek + $data['matches_total'] - 1;
        $starterCount = GameContract::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $team->id)
            ->where('is_starter', true)
            ->activeAt($startWeek)
            ->count();

        // Premier numéro disponible dans l'équipe
        $usedNumbers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereHas('contracts', fn($q) => $q->where('game_team_id', $team->id)->activeAt($startWeek))
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
        $this->authorizeGameSave('update', $gameSave);
        $gameSave->fill($request->validated())->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Sauvegarde mise à jour.');
    }

    public function destroy(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeGameSave('delete', $gameSave);
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

        return redirect()->route('game-saves.Play', $lastSave);
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
}

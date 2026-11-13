<?php

namespace App\Http\Controllers\GameSaves;

use App\Helpers\HeadingRating;
use App\Helpers\InitialPlayerStats;
use App\Http\Controllers\Controller;
use App\Models\BonusCard;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Http\Requests\GameSaves\GameSaveRequest;
use App\Models\Contract;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameTeam;
use App\Models\PeriodPackage;
use App\Models\Player;
use App\Models\PlayerLink;
use App\Models\Team;
use App\Enums\Nationality;
use App\Services\AiLineUpService;
use App\Services\BonusCardShopService;
use App\Services\CareerObjectiveService;
use App\Services\SeasonChallengeService;
use App\Services\DraftService;
use App\Services\MoraleService;
use App\Services\PlayerMoraleSummary;
use App\Services\PlayerChemistryService;
use App\Services\NationalTeamAssembler;
use App\Services\SeasonService;
use App\Services\PlayerStatsService;
use App\Services\TournamentService;
use App\Services\WeekClosingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GameSaveController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

    /** Nombre de sélections participant à une Coupe du Monde. */
    private const TOURNAMENT_NATIONS = 8;

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

    public function create(Request $request): Response
    {
        return Inertia::render('GameSaves/Create', [
            'periodPackages' => $this->periodPackagesSummary($request->user()),
            'draftBudgets' => GameSave::DRAFT_BUDGETS,
            'aiDraftBudget' => GameSave::DEFAULT_CONFIG['ai_draft_budget'],
            'aiStartBudget' => GameSave::DEFAULT_CONFIG['ai_start_budget'],
            'humanStartBudgets' => ['none' => CareerObjectiveService::SANDBOX_START_BUDGET]
                + array_map(fn (array $preset) => $preset['start_budget'], CareerObjectiveService::PRESETS),
        ]);
    }

    /**
     * Métadonnées légères des packages de période visibles par l'utilisateur
     * (les siens + les partagés, tout pour un admin) — pour le sélecteur de
     * contenu à la création d'une partie. Pas l'effectif complet : celui-ci
     * n'est chargé qu'une fois le package effectivement choisi (cf. store()).
     */
    private function periodPackagesSummary(\App\Models\User $user): Collection
    {
        return PeriodPackage::query()
            ->visibleTo($user)
            ->get(['id', 'name', 'description', 'player_count', 'team_count'])
            ->map(fn (PeriodPackage $p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'description' => $p->description,
                'playerCount' => $p->player_count,
                'teamCount'   => $p->team_count,
            ])
            ->values();
    }

    /**
     * Étape 1 : reçoit label + period + format → affiche l'écran de sélection.
     * - college_league : choix des clubs (TeamSelection)
     * - world_cup      : choix de la sélection nationale (NationSelection)
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label'              => ['nullable', 'string', 'max:255'],
            'period'             => ['required', 'string', 'in:college'],
            'game_mode'          => ['nullable', 'string', 'in:prebuilt,draft'],
            'competition_type'   => ['nullable', 'string', 'in:college_league,world_cup'],
            'game_config'        => ['nullable', 'array'],
            'period_package_id'  => ['nullable', 'integer', 'exists:period_packages,id'],
            ...GameSave::configRules('game_config.'),
        ]);
        $data['game_config'] = GameSave::sanitizeConfig($data['game_config'] ?? null) ?: null;

        if ($data['game_config'] && ! (new GameSave)->hasValidTrainingGains($data['game_config'])) {
            return back()->withErrors(['game_config' => 'Le gain max doit être supérieur ou égal au gain min.']);
        }

        // L'étape 1 est gardée en session et l'écran de choix d'équipe servi
        // en GET : un F5, ou une erreur de validation au lancement (back()),
        // y ramène au lieu de renvoyer sur la liste des parties.
        $data['player_stats_seed'] = random_int(1, 2_000_000_000);
        $request->session()->put('game_save_setup', $data);

        return redirect()->route('game-saves.setup');
    }

    /**
     * Étape 2 de la création : choix de l'équipe (ou de la nation), à partir
     * des réglages de l'étape 1 gardés en session.
     */
    public function setup(Request $request): Response|RedirectResponse
    {
        $data = $request->session()->get('game_save_setup');
        if (!is_array($data) || empty($data['period'])) {
            return redirect()->route('game-saves.create');
        }

        $competitionType = $data['competition_type'] ?? 'college_league';
        $gameConfig = $data['game_config'] ?? null;
        $gameMode = $data['game_mode'] ?? 'prebuilt';

        if ($competitionType === 'world_cup') {
            return Inertia::render('GameSaves/NationSelection', [
                'label'           => $data['label'] ?? null,
                'period'          => $data['period'],
                'competitionType' => $competitionType,
                'nations'         => $this->playableNations(),
                'gameConfig'      => $gameConfig,
            ]);
        }

        // Contenu choisi à l'étape précédente (Create.vue) : soit un package de
        // période (remplace entièrement le roster standard), soit l'effectif
        // standard. Sans effet en mode draft — le draft pioche dans le pool
        // global de joueurs libres, incompatible avec un roster de package
        // pré-assigné (cf. GameSaveRequest::rules(), Create.vue).
        $package = null;
        if ($gameMode !== 'draft' && !empty($data['period_package_id'])) {
            $package = PeriodPackage::query()
                ->visibleTo($request->user())
                ->findOrFail($data['period_package_id']);
        }

        $seed = $data['player_stats_seed'] ?? random_int(1, 2_000_000_000);
        $request->session()->put('game_save_setup.player_stats_seed', $seed);
        $previewSave = new GameSave(['state' => ['config' => $gameConfig ?? []]]);
        $previewStats = static function (int|string $id, int $baseSpeed) use ($previewSave, $seed): array {
            $stats = InitialPlayerStats::forPlayer($previewSave, $seed, $id, $baseSpeed);

            $stats['morale_preview'] = $stats['morale'];
            unset($stats['morale']);

            return $stats;
        };
        $periodPackage = null;

        if ($package) {
            // Équipes du package façonnées comme des `Team` canon (id synthétique
            // = team_key, contracts[].player) pour que TeamSelection.vue affiche
            // le même aperçu d'effectif complet qu'en mode standard — sans ça,
            // l'écran ne montre qu'un résumé vide (cf. bug rapporté).
            $playersByKey    = collect($package->players_json)->keyBy('player_key');
            $contractsByTeam = collect($package->contracts_json)->groupBy('team_key');

            $teams = collect($package->teams_json)->map(function ($t) use ($contractsByTeam, $playersByKey, $previewStats) {
                $contracts = ($contractsByTeam->get($t['team_key']) ?? collect())
                    ->map(function ($c) use ($playersByKey, $previewStats) {
                        $p = $playersByKey->get($c['player_key']);
                        if (!$p) return null;

                        return [
                            'salary'     => $c['salary'] ?? 0,
                            'is_captain' => (bool) ($c['is_captain'] ?? false),
                            'player'     => [
                                'id'                  => $p['player_key'],
                                'firstname'           => $p['firstname'],
                                'lastname'            => $p['lastname'],
                                'position'            => $p['position'],
                                'secondary_positions' => $p['secondary_positions'] ?? [],
                                'nationality'         => $p['nationality'] ?? null,
                                'origin'              => $p['origin'] ?? null,
                                'photo_path'          => $p['photo_path'] ?? null,
                                ...$previewStats($p['player_key'], (int) ($p['speed'] ?? 50)),
                                'attack'              => $p['attack']     ?? 50,
                                'defense'             => $p['defense']    ?? 50,
                                'shot'                => $p['shot']       ?? 50,
                                'pass'                => $p['pass']       ?? 50,
                                'dribble'             => $p['dribble']    ?? 50,
                                'block'               => $p['block']      ?? 50,
                                'intercept'           => $p['intercept']  ?? 50,
                                'tackle'              => $p['tackle']     ?? 50,
                                'heading'             => $p['heading'] ?? HeadingRating::forProfile($p['position'], $p),
                                'hand_save'           => $p['hand_save'] ?? 0,
                                'punch_save'          => $p['punch_save'] ?? 0,
                                'special_moves'       => $p['special_moves'] ?? [],
                            ],
                        ];
                    })
                    ->filter()
                    ->values();

                return [
                    'id'                     => $t['team_key'],
                    'name'                   => $t['name'],
                    'description'            => $t['description'] ?? null,
                    'budget'                 => $t['budget'] ?? 0,
                    'tactical_style'         => $t['tactical_style'] ?? null,
                    'management_philosophy'  => $t['management_philosophy'] ?? null,
                    'logo_path'              => $t['logo_path'] ?? null,
                    'contracts'              => $contracts,
                ];
            })->values();

            $periodPackage = [
                'id'          => $package->id,
                'name'        => $package->name,
                'description' => $package->description,
                'playerCount' => $package->player_count,
                'teamCount'   => $package->team_count,
            ];
        } else {
            $teams = Team::with(['contracts.player'])->orderBy('name')->get();

            foreach ($teams as $team) {
                foreach ($team->contracts as $contract) {
                    $player = $contract->player;
                    if ($player) {
                        $player->forceFill($previewStats($player->id, (int) ($player->speed ?? $player->stats['speed'] ?? 50)));
                    }
                }
            }
        }

        return Inertia::render('GameSaves/TeamSelection', [
            'humanStartBudget' => (int) $previewSave->getConfig('human_start_budget'),
            'label'           => $data['label'] ?? null,
            'period'          => $data['period'],
            'teams'           => $teams,
            'gameMode'        => $gameMode,
            'competitionType' => $competitionType,
            'gameConfig'      => $gameConfig,
            'draftBudget'     => (int) $previewSave->getConfig('draft_budget'),
            'aiDraftBudget'   => (int) $previewSave->getConfig('ai_draft_budget'),
            'setupToken'      => hash('sha256', (string) $seed),
            'periodPackage'   => $periodPackage,
        ]);
    }

    /**
     * Nations disponibles pour le mode Coupe du Monde, calculées depuis le pool
     * de joueurs (par `nationality`). Une nation est « jouable » si elle peut
     * aligner un onze : au moins 11 joueurs dont 1 gardien. Triées : jouables
     * d'abord, puis par effectif décroissant.
     *
     * @return array<int, array<string, mixed>>
     */
    private function playableNations(): array
    {
        $rows = Player::query()
            ->whereNotNull('nationality')
            ->selectRaw('nationality, COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN position = 'Goalkeeper' THEN 1 ELSE 0 END) as goalkeepers")
            ->groupBy('nationality')
            ->get()
            ->keyBy('nationality');

        $nations = [];
        foreach (\App\Enums\Nationality::ALL as $name) {
            $row   = $rows->get($name);
            $total = (int) ($row->total ?? 0);
            $gk    = (int) ($row->goalkeepers ?? 0);

            $nations[] = [
                'name'        => $name,
                'flag'        => \App\Enums\Nationality::flag($name),
                'total'       => $total,
                'goalkeepers' => $gk,
                'playable'    => $total >= 11 && $gk >= 1,
            ];
        }

        usort($nations, fn($a, $b) => [$b['playable'], $b['total']] <=> [$a['playable'], $a['total']]);

        return $nations;
    }

    public function start(GameSaveRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['game_config'] = GameSave::sanitizeConfig($data['game_config'] ?? null) ?: null;
        $setupSeed = $request->session()->get('game_save_setup.player_stats_seed');
        if (isset($data['setup_token']) && (
            $setupSeed === null || !hash_equals(hash('sha256', (string) $setupSeed), $data['setup_token'])
        )) {
            return redirect()->route('game-saves.setup')
                ->withErrors(['setup_token' => 'La configuration a changé. Vérifie les nouvelles valeurs avant de confirmer.']);
        }
        $seed = $setupSeed ?? random_int(1, 2_000_000_000);
        $gameMode = $data['game_mode'] ?? 'prebuilt';
        $isDraft  = $gameMode === 'draft';

        // Package de période sélectionné à la place du roster standard.
        // Ignoré en mode draft : le draft tire son sens du pool global de
        // joueurs libres (cf. GameSaveRequest::rules(), TeamSelection.vue).
        $package = null;
        if (!$isDraft && !empty($data['period_package_id'])) {
            $package = PeriodPackage::findOrFail($data['period_package_id']);
            $this->authorize('view', $package);
        }

        // Équipes humaines, dans l'ordre des sièges. Fallback mono-équipe via team_id.
        // En mode package, ce sont des team_key (string) et non des id (int).
        $humanTeamIds = $package
            ? array_values(array_unique($data['team_ids'] ?? array_filter([$data['team_id'] ?? null])))
            : array_values(array_unique(array_map(
                'intval',
                $data['team_ids'] ?? array_filter([$data['team_id'] ?? null]),
            )));

        // Équipe propriétaire / active de la save = siège 1. Pas de correspondance
        // dans la table globale `teams` en mode package (FK nullable → null).
        $primaryTeamId = $package ? null : ($humanTeamIds[0] ?? $data['team_id']);

        $initialState = null;
        if (!empty($data['game_config'])) {
            $initialState = ['config' => $data['game_config']];
        }

        // Tout ou rien : une erreur en cours de route ne laisse pas de partie à moitié construite.
        return DB::transaction(function () use ($request, $data, $package, $isDraft, $gameMode, $primaryTeamId, $initialState, $humanTeamIds, $seed) {
            $gameSave = GameSave::create([
                'user_id' => $request->user()->id,
                'team_id' => $primaryTeamId,
                'period'  => $data['period'],
                'season'  => 1,
                'week'    => 1,
                'phase'   => $isDraft ? 'draft' : 'season',
                'game_mode' => $gameMode,
                'competition_type' => $data['competition_type'] ?? 'college_league',
                'label'   => $data['label'] ?? null,
                'state'   => $initialState,
            ]);

            // 1. Dupliquer les équipes (ou hydrater depuis le package de période)
            $gameTeamsByBaseId = [];

            if ($package) {
                $packageTeams = $package->teams_json;
                $teamCount    = count($packageTeams);
                $seasonLength = SeasonService::lengthForTeamCount($teamCount);

                foreach ($packageTeams as $t) {
                    $gameTeam = GameTeam::create([
                        'game_save_id'          => $gameSave->id,
                        'base_team_id'          => null,
                        'name'                  => $t['name'],
                        'description'           => $t['description'] ?? null,
                        'budget'                => $t['budget'] ?? 0,
                        'wins'                  => 0,
                        'draws'                 => 0,
                        'losses'                => 0,
                        'logo_path'             => $t['logo_path'] ?? null,
                        'formation'             => $t['formation'] ?? '3-2-3-2',
                        'tactical_style'        => $t['tactical_style']        ?? 'balanced',
                        'management_philosophy' => $t['management_philosophy'] ?? 'collective',
                    ]);
                    $gameTeamsByBaseId[$t['team_key']] = $gameTeam;
                }
            } else {
                $teams        = Team::orderBy('id')->get();
                $teamCount    = $teams->count();
                $seasonLength = SeasonService::lengthForTeamCount($teamCount);

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
            }

            // Budget de départ des clubs IA (réglage de création, les humains sont ajustés ci-dessous).
            $aiStartBudget = (int) $gameSave->getConfig('ai_start_budget', 2500);
            foreach ($gameTeamsByBaseId as $baseId => $gameTeam) {
                if (!in_array($baseId, $humanTeamIds, false)) {
                    $gameTeam->update(['budget' => $aiStartBudget]);
                }
            }

            // Marquer chaque équipe humaine (is_controlled + siège dans l'ordre choisi).
            $humanStartBudget = (int) $gameSave->getConfig('human_start_budget');

            $seat = 1;
            $firstControlled = null;
            foreach ($humanTeamIds as $tid) {
                $gameTeam = $gameTeamsByBaseId[$tid] ?? null;
                if (!$gameTeam) {
                    continue;
                }
                $gameTeam->is_controlled = true;
                $gameTeam->human_seat    = $seat++;
                $gameTeam->budget        = $humanStartBudget;
                $gameTeam->save();
                $firstControlled ??= $gameTeam;
            }

            // Joueur actif = siège 1.
            if ($firstControlled) {
                $gameSave->controlled_game_team_id = $firstControlled->id;
                $gameSave->save();
            }

            // 2. Joueurs (package de période ou base globale), indexés par clé source.
            $playerSources = $package
                ? collect($package->players_json)->keyBy('player_key')
                : Player::orderBy('id')->get()->keyBy('id');

            // Contrats (mode prebuilt uniquement) : on prépare d'abord le plan et
            // les numéros de maillot (titulaires 1-11, remplaçants 12+), pour que
            // les joueurs soient insérés avec leur numéro en une seule passe.
            $contractPlan  = [];
            $jerseyNumbers = [];
            if (!$isDraft) {
                $contractsByTeam = $package
                    ? collect($package->contracts_json)->groupBy('team_key')
                    : Contract::orderBy('id')->get()->groupBy('team_id');

                foreach ($contractsByTeam as $teamKey => $teamContracts) {
                    if (!isset($gameTeamsByBaseId[$teamKey])) continue;

                    $starterNumber = 1;
                    $subNumber     = 12;

                    foreach ($teamContracts->values() as $index => $c) {
                        $playerKey = $package ? $c['player_key'] : $c->player_id;
                        if (!$playerSources->has($playerKey)) continue;

                        $isStarter = $index < 11;
                        $jerseyNumbers[$playerKey] = $isStarter ? $starterNumber++ : $subNumber++;

                        $contractPlan[] = [
                            'game_team_id' => $gameTeamsByBaseId[$teamKey]->id,
                            'player_key'   => $playerKey,
                            'salary'       => ($package ? ($c['salary'] ?? 0) : $c->salary) ?? 0,
                            'is_starter'   => $isStarter,
                            'is_captain'   => ($package ? ($c['is_captain'] ?? false) : $c->is_captain) ?? false,
                        ];
                    }
                }
            }

            // Insertion en masse : contourne GamePlayer::creating, mais luck et
            // heading sont toujours fournis ici (InitialPlayerStats / HeadingRating).
            $now        = now();
            $playerRows = [];
            foreach ($playerSources as $key => $src) {
                if ($package) {
                    $p = $src;
                    $row = [
                        'base_player_id'      => null,
                        'firstname'           => $p['firstname'],
                        'lastname'            => $p['lastname'],
                        'position'            => $p['position'],
                        'origin'              => $p['origin'] ?? null,
                        'nationality'         => $p['nationality'] ?? null,
                        'secondary_positions' => json_encode($p['secondary_positions'] ?? []),
                        'description'         => $p['description'] ?? null,
                        'photo_path'          => $p['photo_path'] ?? null,
                        ...InitialPlayerStats::forPlayer($gameSave, $seed, $p['player_key'], (int) ($p['speed'] ?? 50)),
                        'attack'              => $p['attack']     ?? 50,
                        'defense'             => $p['defense']    ?? 50,
                        'shot'                => $p['shot']       ?? 50,
                        'pass'                => $p['pass']       ?? 50,
                        'dribble'             => $p['dribble']    ?? 50,
                        'block'               => $p['block']      ?? 50,
                        'intercept'           => $p['intercept']  ?? 50,
                        'tackle'              => $p['tackle']     ?? 50,
                        'heading'             => $p['heading'] ?? HeadingRating::forProfile($p['position'], $p),
                        'hand_save'           => $p['hand_save']  ?? 0,
                        'punch_save'          => $p['punch_save'] ?? 0,
                        'special_moves'       => json_encode($p['special_moves'] ?? []),
                        'cost'                => $p['cost'] ?? 0,
                    ];
                } else {
                    $player = $src;
                    $s = $player->stats ?? [];
                    $row = [
                        'base_player_id'      => $player->id,
                        'firstname'           => $player->firstname,
                        'lastname'            => $player->lastname,
                        'position'            => $player->position,
                        'origin'              => $player->origin,
                        'nationality'         => $player->nationality,
                        'secondary_positions' => json_encode($player->secondary_positions ?? []),
                        'description'         => $player->description,
                        'photo_path'          => $player->photo_path,
                        ...InitialPlayerStats::forPlayer($gameSave, $seed, $player->id, (int) ($player->speed ?? $s['speed'] ?? 50)),
                        'attack'              => $player->attack     ?? $s['attack']     ?? 50,
                        'defense'             => $player->defense    ?? $s['defense']    ?? 50,
                        'shot'                => $player->shot       ?? $s['shot']       ?? 50,
                        'pass'                => $player->pass       ?? $s['pass']       ?? 50,
                        'dribble'             => $player->dribble    ?? $s['dribble']    ?? 50,
                        'block'               => $player->block      ?? $s['block']      ?? 50,
                        'intercept'           => $player->intercept  ?? $s['intercept']  ?? 50,
                        'tackle'              => $player->tackle     ?? $s['tackle']     ?? 50,
                        'heading'             => $player->headingStat(),
                        'hand_save'           => $player->hand_save  ?? $s['hand_save']  ?? 0,
                        'punch_save'          => $player->punch_save ?? $s['punch_save'] ?? 0,
                        'special_moves'       => json_encode($player->special_moves ?? []),
                        'cost'                => $player->cost ?? 0,
                    ];
                }

                $playerRows[] = ['game_save_id' => $gameSave->id, 'number' => $jerseyNumbers[$key] ?? null]
                    + $row
                    + ['created_at' => $now, 'updated_at' => $now];
            }

            foreach (array_chunk($playerRows, 200) as $chunk) {
                GamePlayer::insert($chunk);
            }

            // Les ids ne sont pas renvoyés par insert() : la save est neuve et
            // n'a que ces joueurs, l'ordre des ids suit donc l'ordre d'insertion.
            $playerIds = GamePlayer::where('game_save_id', $gameSave->id)->orderBy('id')->pluck('id')->all();
            if (count($playerIds) !== count($playerRows)) {
                throw new \RuntimeException('Création de partie : nombre de joueurs inséré incohérent.');
            }
            $gamePlayerIdByKey = array_combine($playerSources->keys()->all(), $playerIds);

            // 3. Contrats
            if (!$isDraft) {
                $contractRows = array_map(fn(array $c) => [
                    'game_save_id'                    => $gameSave->id,
                    'game_team_id'                    => $c['game_team_id'],
                    'game_player_id'                  => $gamePlayerIdByKey[$c['player_key']],
                    'salary'                          => $c['salary'],
                    'start_week'                      => 1,
                    'end_week'                        => $seasonLength,
                    'is_starter'                      => $c['is_starter'],
                    'is_captain'                      => $c['is_captain'],
                    'captain_rerolls_remaining'       => 3,
                    'captain_reroll_used_this_action' => false,
                    'created_at'                      => $now,
                    'updated_at'                      => $now,
                ], $contractPlan);

                // Contourne GameContract::created (défi « Recruter X joueurs ») :
                // sans effet ici, state['challenges'] n'existe pas encore.
                foreach (array_chunk($contractRows, 500) as $chunk) {
                    GameContract::insert($chunk);
                }

                // Composition initiale hors draft : les contrats ont été créés
                // dans l'ordre du package/BDD (sans notion de poste) ; on
                // réorganise chaque équipe selon la formation et le niveau des
                // joueurs à leur poste (principal ou secondaire).
                $state = $gameSave->state ?? [];
                foreach ($gameTeamsByBaseId as $gameTeam) {
                    $gameTeam->load('contracts.gamePlayer');
                    app(DraftService::class)->organizeTeamLineup($gameTeam, $gameSave, $state);
                }
                $gameSave->state = $state;
                $gameSave->save();
            }

            // Marché : quelques amateurs libres dès la semaine 1 (filet de sécurité).
            app(\App\Services\AmateurMarketService::class)->ensureFloor($gameSave);

            // 4. Mode draft : ordre des équipes et enveloppes de recrutement.
            if ($isDraft) {
                $teamIds = array_values(array_map(fn($t) => $t->id, $gameTeamsByBaseId));
                shuffle($teamIds);
                app(DraftService::class)->initDraft($gameSave, $teamIds);

                return redirect()->route('game-saves.draft', $gameSave)
                    ->with('success', 'Partie créée — le draft commence !');
            }

            return redirect()->route('game-saves.Play', $gameSave)->with('success', 'Partie créée');
        });
    }

    /**
     * Lance une partie Coupe du Monde : crée la save, tire au sort les nations
     * participantes (la nation choisie + des adversaires jouables) et monte
     * toutes les sélections via le NationalTeamAssembler.
     *
     * Le calendrier du tournoi (poules + bracket) sera généré en Phase 4 ;
     * la save démarre donc en phase 'world_cup' (attente).
     */
    public function startWorldCup(Request $request, NationalTeamAssembler $assembler, TournamentService $tournament): RedirectResponse
    {
        $data = $request->validate([
            'label'            => ['nullable', 'string', 'max:255'],
            'period'           => ['required', 'string', 'in:college'],
            'competition_type' => ['required', 'string', 'in:world_cup'],
            'nation'           => ['required', 'string', Rule::in(Nationality::ALL)],
            'game_config'      => ['nullable', 'array'],
            ...GameSave::configRules('game_config.'),
        ]);
        $data['game_config'] = GameSave::sanitizeConfig($data['game_config'] ?? null) ?: null;

        if ($data['game_config'] && ! (new GameSave)->hasValidTrainingGains($data['game_config'])) {
            return back()->withErrors(['game_config' => 'Le gain max doit être supérieur ou égal au gain min.']);
        }

        // Nations réellement alignables (≥ 11 joueurs dont 1 gardien).
        $playable = collect($this->playableNations())
            ->where('playable', true)
            ->pluck('name');

        if (! $playable->contains($data['nation'])) {
            return back()->withErrors(['nation' => "Cette sélection n'a pas assez de joueurs pour concourir."]);
        }

        $controlled = $data['nation'];
        $opponents  = $playable->reject(fn($n) => $n === $controlled)
            ->shuffle()
            ->take(self::TOURNAMENT_NATIONS - 1)
            ->values();
        $nations = collect([$controlled])->merge($opponents)->all();

        $wcState = null;
        if (!empty($data['game_config'])) {
            $wcState = ['config' => $data['game_config']];
        }

        return DB::transaction(function () use ($request, $data, $assembler, $tournament, $nations, $controlled, $wcState) {
            $gameSave = GameSave::create([
                'user_id'          => $request->user()->id,
                'team_id'          => null,
                'period'           => $data['period'],
                'season'           => 1,
                'week'             => 1,
                'phase'            => 'world_cup',
                'game_mode'        => 'prebuilt',
                'competition_type' => 'world_cup',
                'label'            => $data['label'] ?? null,
                'state'            => $wcState,
            ]);

            $assembler->assemble($gameSave, $nations, $controlled);
            $tournament->generateGroups($gameSave);

            return redirect()->route('game-saves.Play', $gameSave)
                ->with('success', "Coupe du Monde créée — tu diriges {$controlled} !");
        });
    }

    /**
     * Dashboard principal de la session.
     * Agrège les stats saison depuis game_matches.match_stats (source DB).
     */
    public function play(Request $request, GameSave $gameSave): Response|RedirectResponse
    {
        $this->authorizeGameSave('view', $gameSave);

        // Carrière terminée (licencié / titre de carrière) : la session est figée.
        $career = app(CareerObjectiveService::class);
        if ($career->isGameOver($gameSave)) {
            return redirect()->route('game-saves.game-over', $gameSave);
        }

        // Draft en cours (onglet fermé, draft interrompu) : on y retourne au
        // lieu d'ouvrir Play avec des effectifs partiels. Idem fin de saison.
        if (in_array($gameSave->phase, ['draft', 'intersaison_draft'], true)) {
            return redirect()->route('game-saves.draft', $gameSave);
        }
        if ($gameSave->phase === 'season_end') {
            return redirect()->route('game-saves.season-end', $gameSave);
        }

        // Fixe l'objectif de la direction pour la saison courante si besoin
        // (couvre le mode draft : l'effectif n'existe qu'après la draft).
        $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($career) {
            app(\App\Services\DraftService::class)->purgeFinishedDraft($gameSave);

            if (!$gameSave->isInMatchPhase()) return;

            if (($gameSave->phase ?? 'season') === 'season') {
                $career->ensureSeasonMandate($gameSave);
                $challenges = app(SeasonChallengeService::class);
                $challenges->ensureSeasonChallenges($gameSave);
                // Rattrape les objectifs déjà réussis dans les sauvegardes existantes.
                $challenges->payCompleted($gameSave, [(int) $gameSave->controlled_game_team_id]);
            }

            $this->ensureCalendar($gameSave);
            app(AiLineUpService::class)->prepareScheduledTeams($gameSave, (int) ($gameSave->week ?? 1));
        });

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

        $canRenewContracts = $gameSave->competition_type !== 'world_cup' && ($gameSave->phase ?? 'season') === 'season';
        if ($canRenewContracts && $controlledTeam) {
            foreach ($controlledTeam->contracts as $contract) {
                $contract->gamePlayer?->append('renewal_cost');
            }
        }

        // Sans match_stats (déroulé complet de chaque match, qui alourdissait
        // la page à chaque semaine) : le détail se charge à la demande via
        // game-saves.matches.stats. has_stats indique s'il est disponible.
        // Équipes réduites au nom (le reste est déjà dans la prop teams).
        $matches = GameMatch::with(['homeTeam:id,name', 'awayTeam:id,name'])
            ->where('game_save_id', $gameSave->id)
            ->select([
                'id', 'game_save_id', 'week', 'home_team_id', 'away_team_id',
                'status', 'round', 'home_score', 'away_score',
            ])
            ->selectRaw('match_stats IS NOT NULL AS has_stats')
            ->orderBy('week')
            ->get();

        $freePlayers = GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts', fn($q) => $q->activeAt($currentWeek))
            ->excludingFictional()
            ->visibleForConfig($gameSave)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        // ─── Compteurs de l'onglet Gestion (page Base de données) ───
        $managementStats = [
            'playersCount'           => GamePlayer::where('game_save_id', $gameSave->id)->count(),
            'teamsCount'             => $gameTeams->count(),
            'activeContractsCount'   => $gameTeams->sum(fn($team) => $team->contracts->count()),
            'freePlayersCount'       => $freePlayers->count(),
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
                'severity'       => $i->severity->value,
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

        // Formations de poste en cours (joueur indisponible jusqu'à week_return).
        $activePositionTrainings = GamePositionTraining::where('game_save_id', $gameSave->id)
            ->whereNull('completed_at')
            ->get()
            ->map(fn ($t) => [
                'game_player_id'  => $t->game_player_id,
                'target_position' => $t->special_move ? null : $t->target_position,
                'special_label'    => $t->special_move['label'] ?? null,
                'week_start'      => $t->week_start,
                'week_return'     => $t->week_return,
            ]);

        // Jaunes du cycle en cours (vers la prochaine suspension). Les sanctions
        // sont purgées à l'intersaison, donc tout est de la saison courante.
        $suspensionOn3Yellows = (bool) $gameSave->getConfig('suspension_on_3_yellows', true);
        $activeYellowCards = GameSanction::currentYellowCardCycle($gameSave)
            ->get()
            ->groupBy('game_player_id')
            ->map(fn($cards) => $suspensionOn3Yellows ? $cards->count() % 3 : $cards->count())
            ->filter();

        // ─── Moral : derniers changements par joueur (équipe contrôlée) ───
        $controlledPlayerIds = $controlledTeam
            ? $controlledTeam->contracts->pluck('game_player_id')
            : collect();

        // Moral, promesses (en cours + dernières évaluées), dernière déclaration publique.
        $moraleSummary      = app(PlayerMoraleSummary::class);
        $moraleLogs         = $moraleSummary->recentChanges($gameSave, $controlledPlayerIds);
        $playerPromises     = $moraleSummary->promises($gameSave, $controlledPlayerIds);
        $playerDeclarations = $moraleSummary->declarations($gameSave, $controlledPlayerIds);

        // ─── Bonus cards ───
        $controlledTeamId = $gameSave->controlled_game_team_id;

        // Boutique de la semaine : tirée au premier affichage, donc sous verrou
        // (seulement quand elle est périmée) ; la lecture, elle, reste libre.
        $shopService = app(BonusCardShopService::class);
        if ($controlledTeamId && $shopService->isStale($gameSave)) {
            $this->withSaveLock($gameSave, fn (GameSave $locked) => $shopService->ensureFresh($locked));
        }
        $bonusCardOffers = $controlledTeamId
            ? $shopService->getOffersForTeam($gameSave, $controlledTeamId)
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
                    'description'      => $c->bonusCard->effect_type === 'opponent_stamina_drain'
                        ? BonusCard::teamStaminaDrainDescription($c->tier) : $c->bonusCard->description,
                    'icon'             => $c->bonusCard->icon,
                    'kind'             => $c->bonusCard->kind,
                    'target'           => $c->bonusCard->target->value,
                    'execution_phase'  => $c->bonusCard->execution_phase,
                    'effect_type'      => $c->bonusCard->effect_type,
                    'effect_value'     => $c->bonusCard->effect_type === 'opponent_stamina_drain'
                        ? array_replace($c->bonusCard->effect_value ?? [], ['amount' => BonusCard::teamStaminaDrainAmount($c->tier)])
                        : $c->bonusCard->effect_value,
                ])
            : collect();

        // ─── Défis sponsor (cartes finance) de l'équipe contrôlée ───
        $state = $gameSave->state ?? [];
        $sponsorChallenges = $controlledTeamId
            ? array_values(array_filter(
                $state['pending_sponsor_challenges'] ?? [],
                fn ($c) => (int) ($c['game_team_id'] ?? 0) === (int) $controlledTeamId
            ))
            : [];
        $sponsorResults = $controlledTeamId
            ? array_values(array_filter(
                $state['sponsor_results'] ?? [],
                fn ($c) => (int) ($c['game_team_id'] ?? 0) === (int) $controlledTeamId
            ))
            : [];

        // ─── Malus reçus : titulaires consignés contre l'équipe contrôlée ───
        $incomingMalus = $controlledTeamId
            ? array_values(array_filter(
                $state['pending_malus'] ?? [],
                fn ($m) => (int) ($m['target_team_id'] ?? 0) === (int) $controlledTeamId
                    && ($m['effect_type'] ?? '') === 'opponent_bench_starter'
            ))
            : [];

        // ─── Boucliers anti-malus actifs (carte Cellule de crise) ───
        $activeShields = $controlledTeamId
            ? count(array_filter(
                $state['malus_shields'] ?? [],
                fn ($id) => (int) $id === (int) $controlledTeamId
            ))
            : 0;

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

        // Duos d'alchimie (une-deux), par base_player_id : pour badger les
        // joueurs liés (effectif, mercato).
        $duoLinks = PlayerLink::linksWithNamesFor(
            GamePlayer::where('game_save_id', $gameSave->id)
                ->whereNotNull('base_player_id')
                ->distinct()
                ->pluck('base_player_id')
                ->all()
        );

        $chemistry = app(PlayerChemistryService::class);
        $activeContracts = $gameTeams->flatMap(fn ($team) => $team->contracts);
        $activePlayers = $activeContracts->pluck('gamePlayer')->filter();
        $teamByPlayer = $activeContracts->pluck('game_team_id', 'game_player_id');
        $rosterIds = $teamByPlayer->keys()->all();

        return Inertia::render('GameSaves/Play', [
            'midseasonBreakRecovery' => $gameSave->hasPendingMidseasonBreak() ? SeasonService::MIDSEASON_RECOVERY : null,
            'contractSeasonEndWeek' => $canRenewContracts ? SeasonService::lengthForTeamCount($gameTeams->count()) : null,
            'gameSave'            => $gameSave,
            'managementStats'     => $managementStats,
            'duoLinks'            => $duoLinks,
            'playerChemistry'      => $chemistry->summary($gameSave, $activePlayers, $teamByPlayer),
            'learnedDuos'          => $chemistry->confirmedPartners($gameSave, $rosterIds),
            'teams'               => $gameTeams,
            'matches'             => $matches,
            'freePlayers'         => $freePlayers,
            'controlledTeam'      => $controlledTeam,
            'hotSeat'             => $hotSeat,
            'playerSeasonStats'   => $playerSeasonStats,
            'activeInjuries'      => $activeInjuries,
            'activeSuspensions'   => $activeSuspensions,
            'activePositionTrainings' => $activePositionTrainings,
            'trainableSpecialMoves' => fn () => \App\Services\TrainableSpecialMoves::catalog(),
            'specialTrainingCost' => (int) $gameSave->getConfig('special_training_cost'),
            'activeYellowCards'   => $activeYellowCards,
            'moraleLogs'          => $moraleLogs,
            'playerPromises'      => $playerPromises,
            'playerDeclarations'  => $playerDeclarations,
            'bonusCardOffers'     => $bonusCardOffers,
            'bonusCardInventory'  => $bonusCardInventory,
            'sponsorChallenges'   => $sponsorChallenges,
            'sponsorResults'      => $sponsorResults,
            'incomingMalus'       => $incomingMalus,
            'activeShields'       => $activeShields,
            'gameConfig'          => $gameSave->getConfig(),
            // Séances IA de la dernière semaine jouée (celle affichée par défaut) ;
            // les autres semaines se chargent à la demande.
            'aiTrainingInitial'   => [
                'season'  => (int) ($gameSave->season ?? 1),
                'week'    => max(1, (int) ($gameSave->week ?? 1) - 1),
                'entries' => collect($gameSave->state['ai_training_history'] ?? [])
                    ->filter(fn ($e) => (int) ($e['season'] ?? 0) === (int) ($gameSave->season ?? 1)
                        && (int) ($e['week'] ?? 0) === max(1, (int) ($gameSave->week ?? 1) - 1))
                    ->values()->all(),
            ],
            'tournament'          => $gameSave->competition_type === 'world_cup'
                ? app(TournamentService::class)->presentation($gameSave)
                : null,
            'career'              => app(CareerObjectiveService::class)->presentation($gameSave),
            'seasonChallenges'    => app(SeasonChallengeService::class)->presentation($gameSave),
            'incomeModel'         => WeekClosingService::humanIncomeModel($gameSave),
        ]);
    }

    public function signFreeAgent(Request $request, GameSave $gameSave, GamePlayer $player): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave, $player);

        // Coupe du Monde : des sélections nationales ne recrutent pas.
        if ($gameSave->competition_type === 'world_cup') {
            return back()->withErrors(['player' => 'Pas de transferts en Coupe du Monde.']);
        }

        $data = $request->validate([
            'team_id'       => ['required', 'integer', Rule::exists('game_teams', 'id')->where('game_save_id', $gameSave->id)],
            'matches_total' => ['required', 'integer', 'min:1'],
            'reason'        => ['nullable', 'string', 'max:1000'],
        ]);

        // Verrou par save : pas de double contrat ni de double débit sur un
        // double clic (contrat existant et budget relus sous verrou).
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($data, $player) {
            $player->refresh();

            $team = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($data['team_id']);

            $startWeek = $gameSave->week ?? 1;
            $remainingWeeks = max(0, app(SeasonService::class)->getSeasonLength($gameSave) - $startWeek);
            if (($gameSave->phase ?? 'season') !== 'season' || $remainingWeeks === 0) {
                return back()->withErrors(['matches_total' => 'Il ne reste plus de semaines disponibles pour signer un joueur.']);
            }
            if ($data['matches_total'] > $remainingWeeks) {
                return back()->withErrors(['matches_total' => "La durée maximale restante est de {$remainingWeeks} semaine(s)."]);
            }

            $alreadyHasContract = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_player_id', $player->id)
                ->activeAt($startWeek)
                ->exists();

            if ($alreadyHasContract) {
                return back()->with('info', 'Ce joueur a déjà un contrat en cours dans cette partie.');
            }

            // Le salaire fait autorité côté serveur : on ignore toute valeur cliente
            // (sinon n'importe qui pourrait signer un joueur pour 0) et on refuse la
            // signature si l'équipe n'a pas les moyens.
            $salary    = (int) $player->adjusted_cost;
            $totalCost = $salary * $data['matches_total'];
            $budget    = (int) ($team->budget ?? 0);

            if ($totalCost > $budget) {
                $totalFmt  = number_format($totalCost, 0, ',', ' ');
                $salaryFmt = number_format($salary, 0, ',', ' ');
                $budgetFmt = number_format($budget, 0, ',', ' ');
                return back()->withErrors([
                    'contract' => "Budget insuffisant : {$player->full_name} coûte {$totalFmt} "
                        . "({$salaryFmt} × {$data['matches_total']} match(s)), budget disponible {$budgetFmt}.",
                ]);
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
                'salary'                          => $salary,
                'start_week'                      => $startWeek,
                'end_week'                        => $endWeek,
                'is_starter'                      => $starterCount < 11,
                'is_captain'                      => false,
                'captain_rerolls_remaining'       => 3,
                'captain_reroll_used_this_action' => false,
            ]);

            $team->budget = $budget - $totalCost;
            $team->save();

            $state = $gameSave->state ?? [];
            $state['free_agent_signings'][] = [
                'player_id'     => $player->id,
                'team_id'       => $team->id,
                'salary'        => $salary,
                'matches_total' => $data['matches_total'],
                'total_cost'    => $totalCost,
                'reason'        => $data['reason'] ?? null,
                'week'          => $gameSave->week,
            ];
            $gameSave->state = $state;
            $gameSave->save();

            app(SeasonChallengeService::class)->payCompleted($gameSave, [(int) $team->id], ['signings_min']);

            return back()->with('success', 'Joueur signé et budget mis à jour.');
        });
    }

    public function update(GameSaveRequest $request, GameSave $gameSave): mixed
    {
        $this->authorizeGameSave('update', $gameSave);
        // Seul le libellé est modifiable par le client : semaine, saison, équipe
        // et state sont pilotés par le serveur (un state renvoyé par le client
        // est périmé et écraserait les changements faits depuis l'affichage).
        $gameSave->fill($request->safe()->only(['label']))->save();

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

    private function ensureCalendar(GameSave $gameSave): void
    {
        // En Coupe du Monde, le calendrier (poules + élimination directe) est
        // géré par le moteur de tournoi (Phase 4), pas par le round-robin de ligue.
        if ($gameSave->competition_type === 'world_cup') return;

        if ($gameSave->matches()->exists()) return;

        $teamIds   = $gameSave->gameTeams()->pluck('id')->all();
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

    public function updateConfig(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        // Les options de départ sont définies lors de la création de la sauvegarde.
        $rules = Arr::except(GameSave::configRules(), ['initial_morale_random', 'initial_stamina_random', 'initial_speed_random', 'luck_random', 'ai_start_budget', 'human_start_budget', 'ai_draft_budget', 'draft_budget']);
        $data  = GameSave::sanitizeConfig($request->validate($rules));

        // Sous verrou : state.config est relu et réécrit avec tout le state.
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($data) {
            if (! $gameSave->hasValidTrainingGains($data)) {
                return back()->withErrors(['training_gain_max' => 'Le gain max doit être supérieur ou égal au gain min.']);
            }

            $gameSave->setConfig($data);

            return back()->with('success', 'Configuration mise à jour.');
        });
    }
}

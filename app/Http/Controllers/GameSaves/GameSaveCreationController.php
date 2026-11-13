<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Support\GameConfig;
use App\Helpers\HeadingRating;
use App\Helpers\InitialPlayerStats;
use App\Http\Requests\GameSaves\GameSaveRequest;
use App\Models\Contract;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\PeriodPackage;
use App\Models\Player;
use App\Models\Team;
use App\Enums\Nationality;
use App\Services\CareerObjectiveService;
use App\Services\DraftService;
use App\Services\NationalTeamAssembler;
use App\Services\SeasonBootstrapService;
use App\Services\SeasonService;
use App\Services\TournamentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Création d'une partie : choix des réglages (étape 1), de l'équipe ou de la
 * nation (étape 2), puis lancement Ligue ou Coupe du Monde.
 */
class GameSaveCreationController extends Controller
{

    /** Nombre de sélections participant à une Coupe du Monde. */
    private const TOURNAMENT_NATIONS = 8;

    public function create(Request $request): Response
    {
        return Inertia::render('GameSaves/Create', [
            'periodPackages' => $this->periodPackagesSummary($request->user()),
            'draftBudgets' => GameConfig::DRAFT_BUDGETS,
            'aiDraftBudget' => GameConfig::DEFAULTS['ai_draft_budget'],
            'aiStartBudget' => GameConfig::DEFAULTS['ai_start_budget'],
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
            ...GameConfig::rules('game_config.'),
        ]);
        $data['game_config'] = GameConfig::sanitize($data['game_config'] ?? null) ?: null;

        if ($data['game_config'] && ! (new GameConfig)->hasValidTrainingGains($data['game_config'])) {
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
        if ($refused = $this->refuseIfSaveLimitReached($request)) return $refused;

        $data = $request->validated();
        $data['game_config'] = GameConfig::sanitize($data['game_config'] ?? null) ?: null;
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

            // Première semaine : mandat, défis, calendrier, compositions IA.
            app(SeasonBootstrapService::class)->prepare($gameSave);

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
        if ($refused = $this->refuseIfSaveLimitReached($request)) return $refused;

        $data = $request->validate([
            'label'            => ['nullable', 'string', 'max:255'],
            'period'           => ['required', 'string', 'in:college'],
            'competition_type' => ['required', 'string', 'in:world_cup'],
            'nation'           => ['required', 'string', Rule::in(Nationality::ALL)],
            'game_config'      => ['nullable', 'array'],
            ...GameConfig::rules('game_config.'),
        ]);
        $data['game_config'] = GameConfig::sanitize($data['game_config'] ?? null) ?: null;

        if ($data['game_config'] && ! (new GameConfig)->hasValidTrainingGains($data['game_config'])) {
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
            app(SeasonBootstrapService::class)->prepare($gameSave);

            return redirect()->route('game-saves.Play', $gameSave)
                ->with('success', "Coupe du Monde créée — tu diriges {$controlled} !");
        });
    }

    /** Plafond de sauvegardes par utilisateur (config game.max_saves_per_user). */
    private function refuseIfSaveLimitReached(Request $request): ?RedirectResponse
    {
        $max = (int) config('game.max_saves_per_user');
        if ($max > 0 && GameSave::where('user_id', $request->user()->id)->count() >= $max) {
            return back()->withErrors([
                'game_save' => "Tu as atteint la limite de {$max} parties. Supprime-en une pour en créer une nouvelle.",
            ]);
        }

        return null;
    }

    // ==========================
    //   BUILDERS DE PROPS (play)
    // ==========================
}

<?php

namespace App\Http\Controllers\GameSaves;

use App\Enums\GamePhase;
use App\Enums\Nationality;
use App\Helpers\HeadingRating;
use App\Helpers\InitialPlayerStats;
use App\Http\Controllers\Controller;
use App\Http\Requests\GameSaves\GameSaveRequest;
use App\Http\Requests\GameSaves\StartWorldCupRequest;
use App\Http\Requests\GameSaves\StoreGameSetupRequest;
use App\Models\GameSaves\GameSave;
use App\Models\PeriodPackage;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Services\CareerObjectiveService;
use App\Services\NationalTeamAssembler;
use App\Services\NewGameBuilder;
use App\Services\SeasonBootstrapService;
use App\Services\TournamentService;
use App\Support\GameConfig;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRÉATION D'UNE PARTIE.
 *
 * Ce contrôleur gère le parcours « Nouvelle partie », en trois temps :
 *
 *   1. create()  — affiche l'écran des réglages (nom, mode, configuration…).
 *   2. store()   — enregistre ces réglages en session (« étape 1 »).
 *   3. setup()   — affiche le choix de l'équipe (Ligue) ou de la nation (Coupe du Monde).
 *   4. start()          — lance une partie LIGUE (la construction est dans NewGameBuilder).
 *      startWorldCup()  — lance une partie COUPE DU MONDE.
 *
 * La logique de construction d'une partie n'est PAS ici :
 *   - Ligue         → App\Services\NewGameBuilder
 *   - Coupe du Monde → App\Services\NationalTeamAssembler + TournamentService
 *
 * Ce contrôleur se limite à : vérifier les droits et les limites, lire la
 * requête, appeler le bon service, puis rediriger.
 */
class GameSaveCreationController extends Controller
{
    /** Nombre de sélections qui participent à une Coupe du Monde. */
    private const TOURNAMENT_NATIONS = 8;

    // ══════════════════════════════════════════════════════════════
    //   ÉTAPE 1 : RÉGLAGES
    // ══════════════════════════════════════════════════════════════

    /**
     * Affiche l'écran de création : réglages de la partie, packages de période
     * disponibles et valeurs par défaut des budgets (draft, clubs IA, humains).
     */
    public function create(Request $request): Response
    {
        return Inertia::render('GameSaves/Create', [
            'periodPackages'    => $this->periodPackagesSummary($request->user()),
            'draftBudgets'      => GameConfig::DRAFT_BUDGETS,
            'aiDraftBudget'     => GameConfig::DEFAULTS['ai_draft_budget'],
            'aiStartBudget'     => GameConfig::DEFAULTS['ai_start_budget'],
            // Budget de départ du manager selon la difficulté de carrière choisie.
            'humanStartBudgets' => ['none' => CareerObjectiveService::SANDBOX_START_BUDGET]
                + array_map(fn (array $preset) => $preset['start_budget'], CareerObjectiveService::PRESETS),
        ]);
    }

    /**
     * Liste légère des packages de période visibles par l'utilisateur (les
     * siens, les partagés, et tous pour un administrateur), pour le sélecteur
     * de contenu de l'écran de création.
     *
     * On n'envoie ni les équipes ni les joueurs : l'effectif complet n'est
     * chargé qu'une fois le package réellement choisi (cf. setup()).
     */
    private function periodPackagesSummary(User $user): Collection
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
     * Enregistre les réglages de l'étape 1 EN SESSION puis envoie vers l'étape 2.
     *
     * Rien n'est créé en base à ce stade. Garder les réglages en session permet
     * de resservir l'écran de choix d'équipe en GET : un rechargement (F5) ou une
     * erreur au lancement ramène sur ce choix au lieu de la liste des parties.
     *
     * On tire aussi ici la « graine » des stats initiales des joueurs, pour que
     * l'aperçu affiché à l'étape 2 soit identique à la partie réellement créée.
     */
    public function store(StoreGameSetupRequest $request): RedirectResponse
    {
        $data = $request->setupData();

        $data['player_stats_seed'] = random_int(1, 2_000_000_000);
        $request->session()->put('game_save_setup', $data);

        return redirect()->route('game-saves.setup');
    }

    // ══════════════════════════════════════════════════════════════
    //   ÉTAPE 2 : CHOIX DE L'ÉQUIPE OU DE LA NATION
    // ══════════════════════════════════════════════════════════════

    /**
     * Affiche l'étape 2 d'après les réglages gardés en session :
     *   - Coupe du Monde → liste des nations jouables ;
     *   - Ligue          → équipes (standard ou package de période) avec un aperçu
     *                      de leurs joueurs, tels qu'ils seront à la création.
     *
     * Sans réglages en session (accès direct, session expirée), renvoie à l'étape 1.
     */
    public function setup(Request $request): Response|RedirectResponse
    {
        $data = $request->session()->get('game_save_setup');
        if (! is_array($data) || empty($data['period'])) {
            return redirect()->route('game-saves.create');
        }

        $competitionType = $data['competition_type'] ?? 'college_league';
        $gameConfig      = $data['game_config'] ?? null;
        $gameMode        = $data['game_mode'] ?? 'prebuilt';

        // Coupe du Monde : on choisit une nation, pas un club.
        if ($competitionType === 'world_cup') {
            return Inertia::render('GameSaves/NationSelection', [
                'label'           => $data['label'] ?? null,
                'period'          => $data['period'],
                'competitionType' => $competitionType,
                'nations'         => $this->playableNations(),
                'gameConfig'      => $gameConfig,
            ]);
        }

        // Ligue : package de période choisi à l'étape 1 (il remplace alors tout
        // l'effectif standard). Ignoré en mode draft : le draft pioche dans le
        // pool global de joueurs libres, incompatible avec un effectif de package
        // déjà réparti entre les équipes.
        $package = null;
        if ($gameMode !== 'draft' && ! empty($data['period_package_id'])) {
            $package = PeriodPackage::query()
                ->visibleTo($request->user())
                ->findOrFail($data['period_package_id']);
        }

        // Graine des stats initiales : reprise de l'étape 1 (ou tirée si absente),
        // puis re-mémorisée pour que start() retrouve exactement la même.
        $seed = $data['player_stats_seed'] ?? random_int(1, 2_000_000_000);
        $request->session()->put('game_save_setup.player_stats_seed', $seed);

        // Aperçu des stats avec les réglages de la partie (moral/endurance/vitesse aléatoires…).
        $previewSave  = new GameSave(['state' => ['config' => $gameConfig ?? []]]);
        $previewStats = $this->previewStatsFactory($previewSave, $seed);

        return Inertia::render('GameSaves/TeamSelection', [
            'humanStartBudget' => (int) $previewSave->getConfig('human_start_budget'),
            'label'            => $data['label'] ?? null,
            'period'           => $data['period'],
            'teams'            => $package
                ? $this->packageTeamsForPreview($package, $previewStats)
                : $this->standardTeamsForPreview($previewStats),
            'gameMode'         => $gameMode,
            'competitionType'  => $competitionType,
            'gameConfig'       => $gameConfig,
            'draftBudget'      => (int) $previewSave->getConfig('draft_budget'),
            'aiDraftBudget'    => (int) $previewSave->getConfig('ai_draft_budget'),
            // Empreinte de la graine : start() vérifie qu'elle n'a pas changé entre
            // l'affichage et la confirmation (sinon l'aperçu ne correspondrait plus).
            'setupToken'       => hash('sha256', (string) $seed),
            'periodPackage'    => $package ? [
                'id'          => $package->id,
                'name'        => $package->name,
                'description' => $package->description,
                'playerCount' => $package->player_count,
                'teamCount'   => $package->team_count,
            ] : null,
        ]);
    }

    /**
     * Fabrique la fonction qui calcule les stats « de départ » d'un joueur pour
     * l'aperçu : vitesse, endurance, moral et chance, d'après la graine.
     * Le moral est renommé « morale_preview » pour ne pas être pris pour la valeur finale.
     *
     * @return Closure(int|string, int): array
     */
    private function previewStatsFactory(GameSave $previewSave, int $seed): Closure
    {
        return static function (int|string $id, int $baseSpeed) use ($previewSave, $seed): array {
            $stats = InitialPlayerStats::forPlayer($previewSave, $seed, $id, $baseSpeed);

            $stats['morale_preview'] = $stats['morale'];
            unset($stats['morale']);

            return $stats;
        };
    }

    /**
     * Équipes d'un package de période, mises en forme comme des équipes de la
     * table globale (id = team_key, contrats → joueur) pour que l'écran
     * TeamSelection affiche le même aperçu d'effectif complet qu'en mode standard.
     */
    private function packageTeamsForPreview(PeriodPackage $package, Closure $previewStats): Collection
    {
        $playersByKey    = collect($package->players_json)->keyBy('player_key');
        $contractsByTeam = collect($package->contracts_json)->groupBy('team_key');

        return collect($package->teams_json)->map(function ($t) use ($contractsByTeam, $playersByKey, $previewStats) {
            $contracts = ($contractsByTeam->get($t['team_key']) ?? collect())
                ->map(function ($c) use ($playersByKey, $previewStats) {
                    $p = $playersByKey->get($c['player_key']);
                    if (! $p) {
                        return null; // contrat vers un joueur absent du package : ignoré
                    }

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
                            'attack'              => $p['attack'] ?? 50,
                            'defense'             => $p['defense'] ?? 50,
                            'shot'                => $p['shot'] ?? 50,
                            'pass'                => $p['pass'] ?? 50,
                            'dribble'             => $p['dribble'] ?? 50,
                            'block'               => $p['block'] ?? 50,
                            'intercept'           => $p['intercept'] ?? 50,
                            'tackle'              => $p['tackle'] ?? 50,
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
                'id'                    => $t['team_key'],
                'name'                  => $t['name'],
                'description'           => $t['description'] ?? null,
                'budget'                => $t['budget'] ?? 0,
                'tactical_style'        => $t['tactical_style'] ?? null,
                'management_philosophy' => $t['management_philosophy'] ?? null,
                'logo_path'             => $t['logo_path'] ?? null,
                'contracts'             => $contracts,
            ];
        })->values();
    }

    /**
     * Équipes de la table globale (effectif standard), avec leurs contrats et
     * joueurs, auxquels on ajoute les stats de départ de l'aperçu.
     */
    private function standardTeamsForPreview(Closure $previewStats): Collection
    {
        $teams = Team::with(['contracts.player'])->orderBy('name')->get();

        foreach ($teams as $team) {
            foreach ($team->contracts as $contract) {
                $player = $contract->player;
                if ($player) {
                    $player->forceFill($previewStats($player->id, (int) ($player->speed ?? $player->stats['speed'] ?? 50)));
                }
            }
        }

        return $teams;
    }

    /**
     * Nations proposées pour la Coupe du Monde, calculées d'après le pool de
     * joueurs (colonne « nationality »).
     *
     * Une nation est « jouable » si elle peut aligner un onze : au moins 11
     * joueurs dont 1 gardien. Tri : nations jouables d'abord, puis par effectif
     * décroissant.
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
        foreach (Nationality::ALL as $name) {
            $row   = $rows->get($name);
            $total = (int) ($row->total ?? 0);
            $gk    = (int) ($row->goalkeepers ?? 0);

            $nations[] = [
                'name'        => $name,
                'flag'        => Nationality::flag($name),
                'total'       => $total,
                'goalkeepers' => $gk,
                'playable'    => $total >= 11 && $gk >= 1,
            ];
        }

        usort($nations, fn ($a, $b) => [$b['playable'], $b['total']] <=> [$a['playable'], $a['total']]);

        return $nations;
    }

    // ══════════════════════════════════════════════════════════════
    //   LANCEMENT D'UNE PARTIE
    // ══════════════════════════════════════════════════════════════

    /**
     * Lance une partie LIGUE (mode pré-fait ou draft) après le choix des équipes.
     *
     * Déroulé : limite de parties → cohérence avec l'aperçu (setup_token) →
     * package de période éventuel → construction par NewGameBuilder → redirection
     * (vers le draft, ou vers la partie).
     *
     * Toute la construction (équipes, joueurs, contrats…) est dans NewGameBuilder.
     */
    public function start(GameSaveRequest $request, NewGameBuilder $builder): RedirectResponse
    {
        if ($refused = $this->refuseIfSaveLimitReached($request)) {
            return $refused;
        }

        $data = $request->validated();
        $data['game_config'] = GameConfig::sanitize($data['game_config'] ?? null) ?: null;

        // La graine vient de l'aperçu (session). Si elle ne correspond plus à celle
        // que l'utilisateur a vue, on le renvoie à l'aperçu plutôt que de créer une
        // partie dont les stats diffèrent de ce qu'il a validé.
        $setupSeed = $request->session()->get('game_save_setup.player_stats_seed');
        if (! $this->setupTokenIsValid($data, $setupSeed)) {
            return redirect()->route('game-saves.setup')
                ->withErrors(['setup_token' => 'La configuration a changé. Vérifie les nouvelles valeurs avant de confirmer.']);
        }
        $seed = $setupSeed ?? random_int(1, 2_000_000_000);

        // Le package de période n'a de sens qu'en mode pré-fait (cf. setup()).
        $isDraft = ($data['game_mode'] ?? 'prebuilt') === 'draft';
        $package = $isDraft ? null : $this->selectedPackage($data);

        $gameSave = $builder->build($request->user(), $data, $package, $seed);

        // Draft : on commence par la salle de draft. Pré-fait : on arrive sur la partie.
        if ($gameSave->isDraftPhase()) {
            return redirect()->route('game-saves.draft', $gameSave)
                ->with('success', 'Partie créée — le draft commence !');
        }

        return redirect()->route('game-saves.Play', $gameSave)->with('success', 'Partie créée');
    }

    /**
     * Vérifie que l'empreinte (setup_token) envoyée par le navigateur correspond
     * à la graine gardée en session. Sans empreinte envoyée, il n'y a rien à vérifier.
     *
     * @param mixed $setupSeed graine mémorisée en session (null si la session l'a perdue)
     */
    private function setupTokenIsValid(array $data, mixed $setupSeed): bool
    {
        if (! isset($data['setup_token'])) {
            return true;
        }

        return $setupSeed !== null
            && hash_equals(hash('sha256', (string) $setupSeed), $data['setup_token']);
    }

    /**
     * Package de période choisi pour la partie, ou null (effectif standard).
     * Vérifie que l'utilisateur a le droit de le consulter (le sien, partagé, ou administrateur).
     */
    private function selectedPackage(array $data): ?PeriodPackage
    {
        if (empty($data['period_package_id'])) {
            return null;
        }

        $package = PeriodPackage::findOrFail($data['period_package_id']);
        $this->authorize('view', $package);

        return $package;
    }

    /**
     * Lance une partie COUPE DU MONDE : crée la sauvegarde, tire au sort les
     * nations participantes (la nation choisie + des adversaires jouables), monte
     * chaque sélection (NationalTeamAssembler), génère les poules du tournoi
     * (TournamentService) et prépare la première semaine.
     *
     * Tout est fait dans une transaction : une erreur ne laisse aucune partie à moitié créée.
     */
    public function startWorldCup(StartWorldCupRequest $request, NationalTeamAssembler $assembler, TournamentService $tournament): RedirectResponse
    {
        if ($refused = $this->refuseIfSaveLimitReached($request)) {
            return $refused;
        }

        $data = $request->setupData();

        // Nations réellement alignables (au moins 11 joueurs dont 1 gardien).
        $playable = collect($this->playableNations())
            ->where('playable', true)
            ->pluck('name');

        if (! $playable->contains($data['nation'])) {
            return back()->withErrors(['nation' => "Cette sélection n'a pas assez de joueurs pour concourir."]);
        }

        // La nation de l'utilisateur + des adversaires tirés au sort parmi les autres.
        $controlled = $data['nation'];
        $opponents  = $playable->reject(fn ($n) => $n === $controlled)
            ->shuffle()
            ->take(self::TOURNAMENT_NATIONS - 1)
            ->values();
        $nations = collect([$controlled])->merge($opponents)->all();

        $initialState = ! empty($data['game_config']) ? ['config' => $data['game_config']] : null;

        return DB::transaction(function () use ($request, $data, $assembler, $tournament, $nations, $controlled, $initialState) {
            $gameSave = GameSave::create([
                'user_id'          => $request->user()->id,
                'team_id'          => null,
                'period'           => $data['period'],
                'season'           => 1,
                'week'             => 1,
                'phase'            => GamePhase::WorldCup,
                'game_mode'        => 'prebuilt',
                'competition_type' => 'world_cup',
                'label'            => $data['label'] ?? null,
                'state'            => $initialState,
            ]);

            $assembler->assemble($gameSave, $nations, $controlled); // équipes, joueurs, contrats des sélections
            $tournament->generateGroups($gameSave);                 // poules et calendrier de la phase de groupes
            app(SeasonBootstrapService::class)->prepare($gameSave); // première semaine

            return redirect()->route('game-saves.Play', $gameSave)
                ->with('success', "Coupe du Monde créée — tu diriges {$controlled} !");
        });
    }

    // ══════════════════════════════════════════════════════════════
    //   GARDE-FOU
    // ══════════════════════════════════════════════════════════════

    /**
     * Refuse la création si l'utilisateur a déjà atteint son plafond de parties
     * (config « game.max_saves_per_user », 0 = pas de plafond).
     *
     * @return RedirectResponse|null une redirection avec l'erreur si le plafond est atteint, sinon null
     */
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
}

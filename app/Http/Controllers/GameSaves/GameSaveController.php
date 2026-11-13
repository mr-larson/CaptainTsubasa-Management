<?php

namespace App\Http\Controllers\GameSaves;

use App\Http\Controllers\Controller;
use App\Support\GameConfig;
use App\Models\BonusCard;
use App\Http\Controllers\Concerns\AuthorizesGameSave;
use App\Http\Controllers\Concerns\LocksGameSave;
use App\Http\Requests\GameSaves\GameSaveRequest;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameTeam;
use App\Models\PlayerLink;
use App\Services\BonusCardShopService;
use App\Services\CareerObjectiveService;
use App\Services\SeasonChallengeService;
use App\Services\DraftService;
use App\Services\PlayerMoraleSummary;
use App\Services\PlayerChemistryService;
use App\Services\SeasonBootstrapService;
use App\Services\SeasonService;
use App\Services\PlayerStatsService;
use App\Services\TournamentService;
use App\Services\WeekClosingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GameSaveController extends Controller
{
    use AuthorizesGameSave;
    use LocksGameSave;

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

        // Filet de sécurité pour les sauvegardes antérieures aux transitions
        // explicites (SeasonBootstrapService) : verrou seulement si quelque chose
        // manque, et revérifié sous verrou. En régime normal, aucune écriture.
        $bootstrap = app(SeasonBootstrapService::class);
        if ($bootstrap->needsPreparation($gameSave)) {
            $this->withSaveLock($gameSave, function (GameSave $locked) use ($bootstrap) {
                if (! $bootstrap->needsPreparation($locked)) return;

                app(DraftService::class)->purgeFinishedDraft($locked);
                $bootstrap->prepare($locked);
            });
        }

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
        $playerSeasonStats = PlayerStatsService::cachedAggregateForSave($gameSave);

        $availability = $this->availabilityProps($gameSave, $currentWeek);

        // ─── Moral : derniers changements par joueur (équipe contrôlée) ───
        $controlledPlayerIds = $controlledTeam
            ? $controlledTeam->contracts->pluck('game_player_id')
            : collect();

        // Moral, promesses (en cours + dernières évaluées), dernière déclaration publique.
        $moraleSummary      = app(PlayerMoraleSummary::class);
        $moraleLogs         = $moraleSummary->recentChanges($gameSave, $controlledPlayerIds);
        $playerPromises     = $moraleSummary->promises($gameSave, $controlledPlayerIds);
        $playerDeclarations = $moraleSummary->declarations($gameSave, $controlledPlayerIds);

        $controlledTeamId = $gameSave->controlled_game_team_id;
        $cards = $this->bonusCardProps($gameSave, $controlledTeamId);

        $hotSeat = $this->hotSeatProp($gameSave, $gameTeams);

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
            'activeInjuries'      => $availability['injuries'],
            'activeSuspensions'   => $availability['suspensions'],
            'activePositionTrainings' => $availability['positionTrainings'],
            'trainableSpecialMoves' => fn () => \App\Services\TrainableSpecialMoves::catalog(),
            'specialTrainingCost' => (int) $gameSave->getConfig('special_training_cost'),
            'activeYellowCards'   => $availability['yellowCards'],
            'moraleLogs'          => $moraleLogs,
            'playerPromises'      => $playerPromises,
            'playerDeclarations'  => $playerDeclarations,
            'bonusCardOffers'     => $cards['offers'],
            'bonusCardInventory'  => $cards['inventory'],
            'sponsorChallenges'   => $cards['sponsorChallenges'],
            'sponsorResults'      => $cards['sponsorResults'],
            'incomingMalus'       => $cards['incomingMalus'],
            'activeShields'       => $cards['activeShields'],
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

    /** Blessures, suspensions, formations de poste et jaunes en cours. */
    private function availabilityProps(GameSave $gameSave, int $currentWeek): array
    {
        $injuries = GameInjury::where('game_save_id', $gameSave->id)
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

        $suspensions = GameSanction::where('game_save_id', $gameSave->id)
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
        $positionTrainings = GamePositionTraining::where('game_save_id', $gameSave->id)
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
        $yellowCards = GameSanction::currentYellowCardCycle($gameSave)
            ->get()
            ->groupBy('game_player_id')
            ->map(fn($cards) => $suspensionOn3Yellows ? $cards->count() % 3 : $cards->count())
            ->filter();

        return compact('injuries', 'suspensions', 'positionTrainings', 'yellowCards');
    }

    /** Boutique, inventaire, défis sponsor, malus reçus et boucliers de l'équipe contrôlée. */
    private function bonusCardProps(GameSave $gameSave, ?int $controlledTeamId): array
    {
        // Boutique de la semaine : tirée au premier affichage, donc sous verrou
        // (seulement quand elle est périmée) ; la lecture, elle, reste libre.
        $shopService = app(BonusCardShopService::class);
        if ($controlledTeamId && $shopService->isStale($gameSave)) {
            $this->withSaveLock($gameSave, fn (GameSave $locked) => $shopService->ensureFresh($locked));
        }
        $offers = $controlledTeamId
            ? $shopService->getOffersForTeam($gameSave, $controlledTeamId)
            : [];

        $inventory = $controlledTeamId
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

        return compact('offers', 'inventory', 'sponsorChallenges', 'sponsorResults', 'incomingMalus', 'activeShields');
    }

    /** Qui joue, dans quel ordre (hot-seat multi-manager) ; null en mono-manager. */
    private function hotSeatProp(GameSave $gameSave, Collection $gameTeams): ?array
    {
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

        return $hotSeat;
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

    public function updateConfig(Request $request, GameSave $gameSave): RedirectResponse
    {
        $this->authorizeGameSave('update', $gameSave);

        // Les options de départ sont définies lors de la création de la sauvegarde.
        $rules = Arr::except(GameConfig::rules(), ['initial_morale_random', 'initial_stamina_random', 'initial_speed_random', 'luck_random', 'ai_start_budget', 'human_start_budget', 'ai_draft_budget', 'draft_budget']);
        $data  = GameConfig::sanitize($request->validate($rules));

        // Sous verrou : state.config est relu et réécrit avec tout le state.
        return $this->withSaveLock($gameSave, function (GameSave $gameSave) use ($data) {
            if (! $gameSave->config()->hasValidTrainingGains($data)) {
                return back()->withErrors(['training_gain_max' => 'Le gain max doit être supérieur ou égal au gain min.']);
            }

            $gameSave->setConfig($data);

            return back()->with('success', 'Configuration mise à jour.');
        });
    }
}

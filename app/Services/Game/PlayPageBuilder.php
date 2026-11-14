<?php

namespace App\Services\Game;

use App\Enums\GamePhase;
use App\Http\Resources\GameSaveResource;
use App\Models\BonusCard;
use App\Models\GameSaves\GameBonusCard;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePositionTraining;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\PlayerLink;
use Illuminate\Support\Collection;
use App\Services\Cards\BonusCardShopService;
use App\Services\Players\PlayerChemistryService;
use App\Services\Players\PlayerMoraleSummary;
use App\Services\Players\PlayerStatsService;
use App\Services\Season\CareerObjectiveService;
use App\Services\Season\SeasonChallengeService;
use App\Services\Season\SeasonService;
use App\Services\Season\WeekClosingService;
use App\Services\Training\TrainableSpecialMoves;
use App\Services\WorldCup\TournamentService;

/**
 * Construit toutes les DONNÉES (« props ») de la page de jeu principale
 * (GameSaves/Play) : équipes, calendrier, agents libres, blessures, moral,
 * cartes, défis, tournoi, carrière…
 *
 * build() ne fait que LIRE : il n'écrit rien en base. Les deux seules écritures
 * que la page peut déclencher (rafraîchir la boutique, préparer une saison) se
 * font avant, sous le verrou de la partie, dans GameSaveController::play().
 *
 * Chaque bloc de données est construit par une méthode privée courte et commentée.
 * Les noms des clés renvoyées par build() sont ceux attendus par Play.vue : les
 * renommer casserait la page.
 */
class PlayPageBuilder
{
    public function __construct(
        private BonusCardShopService $shop,
        private PlayerMoraleSummary $moraleSummary,
        private PlayerChemistryService $chemistry,
        private TournamentService $tournament,
        private CareerObjectiveService $career,
        private SeasonChallengeService $seasonChallenges,
    ) {
    }

    // ──────────────────────────────────────────────────────────────
    //   BOUTIQUE (seule écriture liée à la page, faite par le contrôleur sous verrou)
    // ──────────────────────────────────────────────────────────────

    /**
     * Vrai si la boutique de cartes de la semaine est périmée et doit être retirée.
     * Sans équipe contrôlée, il n'y a pas de boutique à afficher.
     */
    public function shopNeedsRefresh(GameSave $gameSave): bool
    {
        return (bool) $gameSave->controlled_game_team_id && $this->shop->isStale($gameSave);
    }

    /** Tire la boutique de la semaine. À appeler SOUS LE VERROU de la partie. */
    public function refreshShop(GameSave $lockedSave): void
    {
        $this->shop->ensureFresh($lockedSave);
    }

    // ──────────────────────────────────────────────────────────────
    //   ASSEMBLAGE
    // ──────────────────────────────────────────────────────────────

    /**
     * Toutes les props de la page de jeu, prêtes pour Inertia.
     *
     * @return array<string, mixed>
     */
    public function build(GameSave $gameSave): array
    {
        $week      = $gameSave->week ?? 1;
        $gameTeams = $this->teams($gameSave, $week);
        $gameSave->load('controlledGameTeam');

        $controlledTeam    = $this->controlledTeam($gameSave, $gameTeams);
        $canRenewContracts = $gameSave->competition_type !== 'world_cup' && $gameSave->currentPhase() === GamePhase::Season;
        if ($canRenewContracts && $controlledTeam) {
            // Coût de prolongation affiché sur chaque joueur de l'équipe contrôlée.
            foreach ($controlledTeam->contracts as $contract) {
                $contract->gamePlayer?->append('renewal_cost');
            }
        }

        $freePlayers  = $this->freePlayers($gameSave, $week);
        $availability = $this->availability($gameSave, $week);
        $morale       = $this->morale($gameSave, $controlledTeam);
        $cards        = $this->bonusCards($gameSave, $gameSave->controlled_game_team_id);
        $roster       = $this->rosterOf($gameTeams);

        return [
            'midseasonBreakRecovery'  => $gameSave->hasPendingMidseasonBreak() ? SeasonService::MIDSEASON_RECOVERY : null,
            'contractSeasonEndWeek'   => $canRenewContracts ? SeasonService::lengthForTeamCount($gameTeams->count()) : null,
            'gameSave'                => GameSaveResource::make($gameSave)->toArray(),
            'managementStats'         => $this->managementStats($gameSave, $gameTeams, $freePlayers),
            'duoLinks'                => $this->duoLinks($gameSave),
            'playerChemistry'         => $this->chemistry->summary($gameSave, $roster['players'], $roster['teamByPlayer']),
            'learnedDuos'             => $this->chemistry->confirmedPartners($gameSave, $roster['playerIds']),
            'teams'                   => $gameTeams,
            'matches'                 => $this->calendar($gameSave),
            'freePlayers'             => $freePlayers,
            'controlledTeam'          => $controlledTeam,
            'hotSeat'                 => $this->hotSeat($gameSave, $gameTeams),
            'playerSeasonStats'       => PlayerStatsService::cachedAggregateForSave($gameSave),
            'activeInjuries'          => $availability['injuries'],
            'activeSuspensions'       => $availability['suspensions'],
            'activePositionTrainings' => $availability['positionTrainings'],
            // Catalogue chargé à la demande (prop paresseuse d'Inertia).
            'trainableSpecialMoves'   => fn () => TrainableSpecialMoves::catalog(),
            'specialTrainingCost'     => (int) $gameSave->getConfig('special_training_cost'),
            'activeYellowCards'       => $availability['yellowCards'],
            'moraleLogs'              => $morale['logs'],
            'playerPromises'          => $morale['promises'],
            'playerDeclarations'      => $morale['declarations'],
            'bonusCardOffers'         => $cards['offers'],
            'bonusCardInventory'      => $cards['inventory'],
            'sponsorChallenges'       => $cards['sponsorChallenges'],
            'sponsorResults'          => $cards['sponsorResults'],
            'incomingMalus'           => $cards['incomingMalus'],
            'activeShields'           => $cards['activeShields'],
            'gameConfig'              => $gameSave->getConfig(),
            'aiTrainingInitial'       => $this->aiTrainingInitial($gameSave),
            'tournament'              => $gameSave->competition_type === 'world_cup'
                ? $this->tournament->presentation($gameSave)
                : null,
            'career'                  => $this->career->presentation($gameSave),
            'seasonChallenges'        => $this->seasonChallenges->presentation($gameSave),
            'incomeModel'             => WeekClosingService::humanIncomeModel($gameSave),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //   ÉQUIPES, CALENDRIER, AGENTS LIBRES
    // ──────────────────────────────────────────────────────────────

    /** Équipes de la partie (ordre alphabétique) avec leurs contrats actifs cette semaine et leurs joueurs. */
    private function teams(GameSave $gameSave, int $week): Collection
    {
        return GameTeam::with(['contracts' => fn ($q) => $q->activeAt($week)->with('gamePlayer')])
            ->where('game_save_id', $gameSave->id)
            ->orderBy('name')
            ->get();
    }

    /**
     * Équipe affichée par défaut : celle du manager ACTIF (hot-seat), sinon l'équipe
     * propriétaire de la partie, sinon la première de la liste.
     */
    private function controlledTeam(GameSave $gameSave, Collection $gameTeams): ?GameTeam
    {
        return $gameTeams->firstWhere('id', $gameSave->controlled_game_team_id)
            ?? $gameTeams->firstWhere('base_team_id', $gameSave->team_id)
            ?? $gameTeams->first();
    }

    /**
     * Calendrier : tous les matchs de la saison, SANS leurs statistiques détaillées
     * (trop lourdes à chaque affichage). `has_stats` indique si le détail existe ; il
     * se charge à la demande via la route game-saves.matches.stats.
     */
    private function calendar(GameSave $gameSave): Collection
    {
        return GameMatch::with(['homeTeam:id,name', 'awayTeam:id,name'])
            ->where('game_save_id', $gameSave->id)
            ->select([
                'id', 'game_save_id', 'week', 'home_team_id', 'away_team_id',
                'status', 'round', 'home_score', 'away_score',
            ])
            ->selectRaw('match_stats IS NOT NULL AS has_stats')
            ->orderBy('week')
            ->get();
    }

    /** Agents libres : joueurs sans contrat actif cette semaine (hors fictifs, selon les origines visibles). */
    private function freePlayers(GameSave $gameSave, int $week): Collection
    {
        return GamePlayer::where('game_save_id', $gameSave->id)
            ->whereDoesntHave('contracts', fn ($q) => $q->activeAt($week))
            ->excludingFictional()
            ->visibleForConfig($gameSave)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();
    }

    /** Compteurs de l'onglet « Gestion » : joueurs, équipes, contrats actifs, agents libres. */
    private function managementStats(GameSave $gameSave, Collection $gameTeams, Collection $freePlayers): array
    {
        return [
            'playersCount'         => GamePlayer::where('game_save_id', $gameSave->id)->count(),
            'teamsCount'           => $gameTeams->count(),
            'activeContractsCount' => $gameTeams->sum(fn ($team) => $team->contracts->count()),
            'freePlayersCount'     => $freePlayers->count(),
        ];
    }

    /**
     * Effectif sous contrat de toute la partie, sous trois formes utiles à l'alchimie :
     * les joueurs, l'équipe de chacun (id joueur → id équipe) et leurs identifiants.
     *
     * @return array{players: Collection, teamByPlayer: Collection, playerIds: array}
     */
    private function rosterOf(Collection $gameTeams): array
    {
        $contracts    = $gameTeams->flatMap(fn ($team) => $team->contracts);
        $teamByPlayer = $contracts->pluck('game_team_id', 'game_player_id');

        return [
            'players'      => $contracts->pluck('gamePlayer')->filter(),
            'teamByPlayer' => $teamByPlayer,
            'playerIds'    => $teamByPlayer->keys()->all(),
        ];
    }

    /**
     * Duos d'alchimie (« une-deux ») par joueur de référence, pour afficher un badge
     * sur les joueurs liés (effectif, mercato).
     */
    private function duoLinks(GameSave $gameSave): mixed
    {
        return PlayerLink::linksWithNamesFor(
            GamePlayer::where('game_save_id', $gameSave->id)
                ->whereNotNull('base_player_id')
                ->distinct()
                ->pluck('base_player_id')
                ->all()
        );
    }

    /**
     * Ordre des managers humains (hot-seat multi-manager) : qui joue et dans quel ordre.
     * Renvoie null s'il n'y a qu'un seul manager.
     */
    private function hotSeat(GameSave $gameSave, Collection $gameTeams): ?array
    {
        $humanTeams = $gameTeams->whereNotNull('human_seat')->sortBy('human_seat')->values();
        if ($humanTeams->isEmpty()) {
            $humanTeams = $gameTeams->where('is_controlled', true)->values();
        }

        if ($humanTeams->count() <= 1) {
            return null;
        }

        $activeId = (int) $gameSave->controlled_game_team_id;

        return [
            'total'   => $humanTeams->count(),
            'players' => $humanTeams->values()->map(fn ($t, $i) => [
                'seat'         => $i + 1,
                'name'         => $t->name,
                'game_team_id' => $t->id,
                'is_active'    => (int) $t->id === $activeId,
            ])->all(),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //   DISPONIBILITÉ DES JOUEURS
    // ──────────────────────────────────────────────────────────────

    /**
     * Joueurs indisponibles ou menacés : blessures et suspensions en cours, formations
     * de poste en cours, et cartons jaunes du cycle (vers la prochaine suspension).
     *
     * @return array{injuries: Collection, suspensions: Collection, positionTrainings: Collection, yellowCards: Collection}
     */
    private function availability(GameSave $gameSave, int $week): array
    {
        $injuries = GameInjury::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $week)
            ->with('gamePlayer')
            ->get()
            ->map(fn ($i) => [
                'game_player_id' => $i->game_player_id,
                'severity'       => $i->severity->value,
                'weeks_out'      => $i->weeks_out,
                'week_return'    => $i->week_return,
                'description'    => $i->description,
            ]);

        $suspensions = GameSanction::where('game_save_id', $gameSave->id)
            ->where('week_return', '>', $week)
            ->where('weeks_suspended', '>', 0)
            ->get()
            ->map(fn ($s) => [
                'game_player_id'  => $s->game_player_id,
                'type'            => $s->type,
                'weeks_suspended' => $s->weeks_suspended,
                'week_return'     => $s->week_return,
            ]);

        // Un joueur en formation de poste est indisponible jusqu'à sa semaine de retour.
        $positionTrainings = GamePositionTraining::where('game_save_id', $gameSave->id)
            ->whereNull('completed_at')
            ->get()
            ->map(fn ($t) => [
                'game_player_id'  => $t->game_player_id,
                'target_position' => $t->special_move ? null : $t->target_position,
                'special_label'   => $t->special_move['label'] ?? null,
                'week_start'      => $t->week_start,
                'week_return'     => $t->week_return,
            ]);

        // Jaunes du cycle en cours (3 jaunes = 1 suspension si l'option est active).
        // Les sanctions sont purgées à l'intersaison : tout est de la saison courante.
        $suspensionOn3Yellows = (bool) $gameSave->getConfig('suspension_on_3_yellows', true);
        $yellowCards = GameSanction::currentYellowCardCycle($gameSave)
            ->get()
            ->groupBy('game_player_id')
            ->map(fn ($cards) => $suspensionOn3Yellows ? $cards->count() % 3 : $cards->count())
            ->filter();

        return compact('injuries', 'suspensions', 'positionTrainings', 'yellowCards');
    }

    // ──────────────────────────────────────────────────────────────
    //   MORAL
    // ──────────────────────────────────────────────────────────────

    /**
     * Moral de l'équipe contrôlée : derniers changements par joueur, promesses (en
     * cours et dernières évaluées) et dernière déclaration publique du coach.
     *
     * @return array{logs: mixed, promises: mixed, declarations: mixed}
     */
    private function morale(GameSave $gameSave, ?GameTeam $controlledTeam): array
    {
        $playerIds = $controlledTeam
            ? $controlledTeam->contracts->pluck('game_player_id')
            : collect();

        return [
            'logs'         => $this->moraleSummary->recentChanges($gameSave, $playerIds),
            'promises'     => $this->moraleSummary->promises($gameSave, $playerIds),
            'declarations' => $this->moraleSummary->declarations($gameSave, $playerIds),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //   CARTES BONUS / MALUS ET DÉFIS SPONSOR
    // ──────────────────────────────────────────────────────────────

    /**
     * Cartes de l'équipe contrôlée : offres de la boutique, inventaire, défis sponsor
     * en cours et derniers résultats, malus reçus (titulaires consignés) et boucliers
     * anti-malus actifs. Tout est vide sans équipe contrôlée.
     *
     * @return array{offers: mixed, inventory: Collection, sponsorChallenges: array, sponsorResults: array, incomingMalus: array, activeShields: int}
     */
    private function bonusCards(GameSave $gameSave, ?int $teamId): array
    {
        $state = $gameSave->gameState();
        $forTeam = fn (array $rows, string $key) => array_values(array_filter(
            $rows,
            fn ($row) => (int) ($row[$key] ?? 0) === (int) $teamId
        ));

        return [
            // Offres de la boutique. Si elle est périmée, on n'affiche rien plutôt que de la
            // régénérer ici : le builder ne fait que LIRE (le contrôleur la rafraîchit avant,
            // sous verrou, via shopNeedsRefresh() / refreshShop()).
            'offers'            => ($teamId && ! $this->shop->isStale($gameSave))
                ? $this->shop->getOffersForTeam($gameSave, $teamId)
                : [],
            'inventory'         => $teamId ? $this->cardInventory($gameSave, $teamId) : collect(),
            // Défis sponsor (cartes finance) de l'équipe : en cours, puis résultats de la dernière clôture.
            'sponsorChallenges' => $teamId ? $forTeam($state->pendingSponsorChallenges(), 'game_team_id') : [],
            'sponsorResults'    => $teamId ? $forTeam($state->sponsorResults(), 'game_team_id') : [],
            // Malus « titulaire consigné » infligés à CETTE équipe pour son prochain match.
            'incomingMalus'     => $teamId
                ? array_values(array_filter(
                    $state->pendingMalus(),
                    fn ($m) => (int) ($m['target_team_id'] ?? 0) === (int) $teamId
                        && ($m['effect_type'] ?? '') === 'opponent_bench_starter'
                ))
                : [],
            // Boucliers anti-malus actifs (carte « Cellule de crise »).
            'activeShields'     => $teamId
                ? count(array_filter($state->malusShields(), fn ($id) => (int) $id === (int) $teamId))
                : 0,
        ];
    }

    /**
     * Inventaire de cartes de l'équipe (achetées, utilisées), mis en forme pour la page.
     * La carte « drain d'endurance » affiche une description et une valeur qui
     * dépendent de son niveau (bronze / argent / or).
     */
    private function cardInventory(GameSave $gameSave, int $teamId): Collection
    {
        return GameBonusCard::where('game_save_id', $gameSave->id)
            ->where('game_team_id', $teamId)
            ->with('bonusCard')
            ->orderByDesc('purchased_week')
            ->get()
            ->map(function ($c) {
                $card       = $c->bonusCard;
                $isStamina  = $card->effect_type === 'opponent_stamina_drain';

                return [
                    'id'               => $c->id,
                    'bonus_card_id'    => $c->bonus_card_id,
                    'status'           => $c->status,
                    'tier'             => $c->tier,
                    'cost_paid'        => $c->cost_paid,
                    'purchased_week'   => $c->purchased_week,
                    'purchased_season' => $c->purchased_season,
                    'used_week'        => $c->used_week,
                    'used_season'      => $c->used_season,
                    'name'             => $card->name,
                    'description'      => $isStamina ? BonusCard::teamStaminaDrainDescription($c->tier) : $card->description,
                    'icon'             => $card->icon,
                    'kind'             => $card->kind,
                    'target'           => $card->target->value,
                    'execution_phase'  => $card->execution_phase,
                    'effect_type'      => $card->effect_type,
                    'effect_value'     => $isStamina
                        ? array_replace($card->effect_value ?? [], ['amount' => BonusCard::teamStaminaDrainAmount($c->tier)])
                        : $card->effect_value,
                ];
            });
    }

    // ──────────────────────────────────────────────────────────────
    //   ENTRAÎNEMENT IA
    // ──────────────────────────────────────────────────────────────

    /**
     * Séances d'entraînement des équipes IA de la DERNIÈRE semaine jouée (celle affichée
     * par défaut dans l'onglet Entraînement). Les autres semaines se chargent à la demande
     * (route game-saves.training.ai-week).
     */
    private function aiTrainingInitial(GameSave $gameSave): array
    {
        $season = (int) ($gameSave->season ?? 1);
        $week   = max(1, (int) ($gameSave->week ?? 1) - 1);

        return [
            'season'  => $season,
            'week'    => $week,
            'entries' => $gameSave->gameState()->aiTrainingHistoryOfWeek($season, $week),
        ];
    }
}

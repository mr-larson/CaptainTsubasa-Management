<?php

namespace App\Services;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;

/**
 * Clôture d'une semaine de jeu, commune à « Terminer le match » (dernier
 * match humain de la semaine) et à « Simuler la semaine ».
 *
 * L'ordre compte : tout ce qui est calculé « pour la semaine jouée »
 * (revenus, cartes, stamina, moral, promesses) s'exécute AVANT l'incrément,
 * car ces services lisent $gameSave->week pour filtrer les contrats actifs.
 *
 * Doit être appelé sous verrou de save (LocksGameSave::withSaveLock).
 */
class WeekClosingService
{

    /** Injecte tous les services appelés pendant la clôture de semaine (IA, simulation, économie, moral, promesses, saison, tournoi, défis...). */
    public function __construct(
        private AiLineUpService $aiLineUpService,
        private MatchSimulator $matchSimulator,
        private AITrainingService $aITrainingService,
        private AITransferService $aITransferService,
        private AmateurMarketService $amateurMarketService,
        private BonusCardActivationService $bonusCardActivationService,
        private MoraleService $moraleService,
        private PromiseService $promiseService,
        private ContractDepartureService $contractDepartureService,
        private SeasonService $seasonService,
        private PositionTrainingService $positionTrainingService,
        private BonusCardShopService $bonusCardShopService,
        private AiBonusCardService $aiBonusCardService,
        private SeasonChallengeService $seasonChallengeService,
        private TournamentService $tournamentService,
        private AiFormationTrainingService $aiFormationTrainingService,
        private AiCoachService $aiCoachService,
        private PlayerChemistryService $playerChemistryService,
        private CareerObjectiveService $careerObjectiveService,
    ) {
    }
    /** Résultats possibles de close() : continuer à jouer, fin de partie (licenciement ou victoire), fin de saison. */
    public const OUTCOME_PLAY       = 'play';
    public const OUTCOME_GAME_OVER  = 'game_over';
    public const OUTCOME_SEASON_END = 'season_end';

    /** Revenu hebdomadaire de base d'une équipe (en GL), avec primes de victoire et de nul. */
    public const BASE_INCOME = 500;
    private const WIN_BONUS   = 300;
    private const DRAW_BONUS  = 100;

    /**
     * Simule les matchs restants de la semaine, puis déroule le pipeline.
     *
     * @return string Une des constantes OUTCOME_* (écran vers lequel rediriger).
     */
    public function close(GameSave $gameSave, int $week): string
    {
        // 1. Préparer les équipes qui n’ont pas encore joué, puis simuler leurs matchs.
        $this->aiLineUpService->prepareScheduledTeams($gameSave, $week);
        $remaining = GameMatch::query()
            ->where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'scheduled')
            ->with([
                'homeTeam' => fn($q) => $q->with(['contracts' => fn($cq) => $cq->activeAt($week)->with('gamePlayer')]),
                'awayTeam' => fn($q) => $q->with(['contracts' => fn($cq) => $cq->activeAt($week)->with('gamePlayer')]),
            ])
            ->get();
        $this->matchSimulator->simulateMatchesCollection($remaining, $gameSave);

        // 2. IA : entraînement et recrutement.
        if ($gameSave->getConfig('ai_training_enabled', true)) {
            $this->aITrainingService->trainForWeek($gameSave);
        }
        if ($gameSave->getConfig('ai_transfers_enabled', true)) {
            $this->aITransferService->recruitForWeek($gameSave);
        }
        // Marché : toujours quelques amateurs libres pour compléter un effectif.
        $this->amateurMarketService->ensureFloor($gameSave);

        // 3. Revenus, défis sponsors, cartes consommées pour la semaine jouée.
        $this->applyWeeklyIncome($gameSave, $week);

        $bonusEnabled = $gameSave->getConfig('bonus_cards_enabled', true);
        $malusEnabled = $bonusEnabled && $gameSave->getConfig('malus_cards_enabled', true);

        $activation = $this->bonusCardActivationService;
        if ($bonusEnabled) {
            $activation->resolveSponsorChallenges($gameSave, $week);
            $activation->consumePreMatchCardsForPlayedWeek($gameSave, $week);
        }

        // 4. Stamina, moral et promesses de la semaine jouée (avant incrément).
        StaminaService::applyAfterWeek($gameSave, $week);
        $this->moraleService->applyAfterWeek($gameSave, $week);
        if ($malusEnabled) {
            $activation->consumeMalusForPlayedWeek($gameSave, $week);
        }
        $this->promiseService->evaluateForWeek($gameSave, $week);
        $this->contractDepartureService->resolveForWeek($gameSave, $week);

        // 5. Semaine suivante : la main repasse au joueur du siège 1.
        $gameSave->week = max((int) ($gameSave->week ?? 1), $week + 1);
        $seasonService = $this->seasonService;
        $seasonService->scheduleMidseasonBreak($gameSave, $week);
        $firstHuman = $gameSave->controlledGameTeams()->first();
        if ($firstHuman) {
            $gameSave->controlled_game_team_id = $firstHuman->id;
        }
        $gameSave->save();

        // Formations de poste arrivées à terme : le joueur revient avec son nouveau poste.
        $this->positionTrainingService->completeDue($gameSave, (int) $gameSave->week);

        if ($bonusEnabled) {
            $this->bonusCardShopService->generateWeeklyOffers($gameSave);
            $this->aiBonusCardService->processWeek($gameSave);
        }
        $this->seasonChallengeService->payCompleted($gameSave);

        // 6. Coupe du Monde : progression du tournoi au lieu de la fin de saison.
        if ($gameSave->competition_type === 'world_cup') {
            $this->tournamentService->advance($gameSave);
            $this->aiFormationTrainingService->planForWeek($gameSave);
            $this->aiCoachService->prepareForWeek($gameSave, $week);
            $this->playerChemistryService->attemptForAiTeams($gameSave);
            return self::OUTCOME_PLAY;
        }

        // 7. Verdict de la direction (jauge de confiance), puis fin de saison.
        $career = $this->careerObjectiveService;
        $career->afterWeek($gameSave, $week);
        if ($career->isGameOver($gameSave)) {
            return self::OUTCOME_GAME_OVER;
        }

        if ($seasonService->isSeasonOver($gameSave)) {
            $seasonService->endSeason($gameSave);
            return self::OUTCOME_SEASON_END;
        }

        $this->aiFormationTrainingService->planForWeek($gameSave);
        $this->aiCoachService->prepareForWeek($gameSave, $week);
        $this->playerChemistryService->attemptForAiTeams($gameSave);

        return self::OUTCOME_PLAY;
    }

    /**
     * Barème des revenus hebdomadaires d'une équipe humaine (affiché au Dashboard
     * pour la projection budgétaire). La difficulté du mandat réduit la base (Ligue).
     *
     * @return array{base: int, win_bonus: int, draw_bonus: int}
     */
    public static function humanIncomeModel(GameSave $gameSave): array
    {
        $base = self::BASE_INCOME;
        if ($gameSave->competition_type !== 'world_cup') {
            $preset = CareerObjectiveService::PRESETS[$gameSave->getConfig('career_difficulty', 'standard')] ?? null;
            if ($preset !== null) {
                $base = $preset['weekly_income'];
            }
        }

        return ['base' => $base, 'win_bonus' => self::WIN_BONUS, 'draw_bonus' => self::DRAW_BONUS];
    }

    /**
     * Revenus hebdomadaires de toutes les équipes : base + bonus de résultat.
     */
    public function applyWeeklyIncome(GameSave $gameSave, int $week): void
    {
        $humanBaseIncome = self::humanIncomeModel($gameSave)['base'];

        // Une seule requête pour les matchs joués de la semaine (pas de N+1).
        $resultByTeam = [];
        GameMatch::where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'played')
            ->get(['home_team_id', 'away_team_id', 'home_score', 'away_score'])
            ->each(function (GameMatch $m) use (&$resultByTeam) {
                $resultByTeam[(int) $m->home_team_id] ??= $m->home_score <=> $m->away_score;
                $resultByTeam[(int) $m->away_team_id] ??= $m->away_score <=> $m->home_score;
            });

        // Le revenu ne prend que quelques valeurs (base / victoire / nul, humain
        // ou IA) : un seul UPDATE par montant plutôt qu'un par équipe.
        $idsByIncome = [];
        foreach (GameTeam::where('game_save_id', $gameSave->id)->get(['id', 'is_controlled']) as $team) {
            $income = $team->is_controlled ? $humanBaseIncome : self::BASE_INCOME;

            $result = $resultByTeam[(int) $team->id] ?? null;
            if ($result === 1) {
                $income += self::WIN_BONUS;
            } elseif ($result === 0) {
                $income += self::DRAW_BONUS;
            }

            $idsByIncome[(int) $income][] = $team->id;
        }

        foreach ($idsByIncome as $income => $teamIds) {
            GameTeam::whereIn('id', $teamIds)
                ->update(['budget' => \Illuminate\Support\Facades\DB::raw('COALESCE(budget, 0) + ' . (int) $income)]);
        }
    }
}

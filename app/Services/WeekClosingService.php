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
    public const OUTCOME_PLAY       = 'play';
    public const OUTCOME_GAME_OVER  = 'game_over';
    public const OUTCOME_SEASON_END = 'season_end';

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
        app(AiLineUpService::class)->prepareScheduledTeams($gameSave, $week);
        $remaining = GameMatch::query()
            ->where('game_save_id', $gameSave->id)
            ->where('week', $week)
            ->where('status', 'scheduled')
            ->with([
                'homeTeam' => fn($q) => $q->with(['contracts' => fn($cq) => $cq->activeAt($week)->with('gamePlayer')]),
                'awayTeam' => fn($q) => $q->with(['contracts' => fn($cq) => $cq->activeAt($week)->with('gamePlayer')]),
            ])
            ->get();
        app(MatchSimulator::class)->simulateMatchesCollection($remaining, $gameSave);

        // 2. IA : entraînement et recrutement.
        if ($gameSave->getConfig('ai_training_enabled', true)) {
            app(AITrainingService::class)->trainForWeek($gameSave);
        }
        if ($gameSave->getConfig('ai_transfers_enabled', true)) {
            app(AITransferService::class)->recruitForWeek($gameSave);
        }
        // Marché : toujours quelques amateurs libres pour compléter un effectif.
        app(AmateurMarketService::class)->ensureFloor($gameSave);

        // 3. Revenus, défis sponsors, cartes consommées pour la semaine jouée.
        $this->applyWeeklyIncome($gameSave, $week);

        $bonusEnabled = $gameSave->getConfig('bonus_cards_enabled', true);
        $malusEnabled = $bonusEnabled && $gameSave->getConfig('malus_cards_enabled', true);

        $activation = app(BonusCardActivationService::class);
        if ($bonusEnabled) {
            $activation->resolveSponsorChallenges($gameSave, $week);
            $activation->consumePreMatchCardsForPlayedWeek($gameSave, $week);
        }

        // 4. Stamina, moral et promesses de la semaine jouée (avant incrément).
        StaminaService::applyAfterWeek($gameSave, $week);
        app(MoraleService::class)->applyAfterWeek($gameSave, $week);
        if ($malusEnabled) {
            $activation->consumeMalusForPlayedWeek($gameSave, $week);
        }
        app(PromiseService::class)->evaluateForWeek($gameSave, $week);
        app(ContractDepartureService::class)->resolveForWeek($gameSave, $week);

        // 5. Semaine suivante : la main repasse au joueur du siège 1.
        $gameSave->week = max((int) ($gameSave->week ?? 1), $week + 1);
        $seasonService = app(SeasonService::class);
        $seasonService->scheduleMidseasonBreak($gameSave, $week);
        $firstHuman = $gameSave->controlledGameTeams()->first();
        if ($firstHuman) {
            $gameSave->controlled_game_team_id = $firstHuman->id;
        }
        $gameSave->save();

        // Formations de poste arrivées à terme : le joueur revient avec son nouveau poste.
        app(PositionTrainingService::class)->completeDue($gameSave, (int) $gameSave->week);

        if ($bonusEnabled) {
            app(BonusCardShopService::class)->generateWeeklyOffers($gameSave);
            app(AiBonusCardService::class)->processWeek($gameSave);
        }
        app(SeasonChallengeService::class)->payCompleted($gameSave);

        // 6. Coupe du Monde : progression du tournoi au lieu de la fin de saison.
        if ($gameSave->competition_type === 'world_cup') {
            app(TournamentService::class)->advance($gameSave);
            app(AiFormationTrainingService::class)->planForWeek($gameSave);
            app(AiCoachService::class)->prepareForWeek($gameSave, $week);
            app(PlayerChemistryService::class)->attemptForAiTeams($gameSave);
            return self::OUTCOME_PLAY;
        }

        // 7. Verdict de la direction (jauge de confiance), puis fin de saison.
        $career = app(CareerObjectiveService::class);
        $career->afterWeek($gameSave, $week);
        if ($career->isGameOver($gameSave)) {
            return self::OUTCOME_GAME_OVER;
        }

        if ($seasonService->isSeasonOver($gameSave)) {
            $seasonService->endSeason($gameSave);
            return self::OUTCOME_SEASON_END;
        }

        app(AiFormationTrainingService::class)->planForWeek($gameSave);
        app(AiCoachService::class)->prepareForWeek($gameSave, $week);
        app(PlayerChemistryService::class)->attemptForAiTeams($gameSave);

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

        foreach (GameTeam::where('game_save_id', $gameSave->id)->get() as $team) {
            $income = $team->is_controlled ? $humanBaseIncome : self::BASE_INCOME;

            $result = $resultByTeam[(int) $team->id] ?? null;
            if ($result === 1) {
                $income += self::WIN_BONUS;
            } elseif ($result === 0) {
                $income += self::DRAW_BONUS;
            }

            $team->budget = ($team->budget ?? 0) + $income;
            $team->save();
        }
    }
}

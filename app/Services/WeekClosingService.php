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

    private const BASE_INCOME = 500;
    private const WIN_BONUS   = 300;
    private const DRAW_BONUS  = 100;

    /**
     * Simule les matchs restants de la semaine, puis déroule le pipeline.
     *
     * @return string Une des constantes OUTCOME_* (écran vers lequel rediriger).
     */
    public function close(GameSave $gameSave, int $week): string
    {
        // 1. Lineups IA ajustés pour les matchs de CETTE semaine (indisponibles,
        //    rotation selon la stamina), puis simulation des matchs restants.
        app(AiLineUpService::class)->adjustLineupsForWeek($gameSave);

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

        // 3. Revenus, défis sponsors, cartes consommées pour la semaine jouée.
        $this->applyWeeklyIncome($gameSave, $week);

        $bonusEnabled = $gameSave->getConfig('bonus_cards_enabled', true);
        $malusEnabled = $bonusEnabled && $gameSave->getConfig('malus_cards_enabled', true);

        $activation = app(BonusCardActivationService::class);
        if ($bonusEnabled) {
            $activation->resolveSponsorChallenges($gameSave, $week);
            $activation->consumePreMatchCardsForPlayedWeek($gameSave, $week);
        }
        if ($malusEnabled) {
            $activation->consumeMalusForPlayedWeek($gameSave, $week);
        }

        // 4. Stamina, moral et promesses de la semaine jouée (avant incrément).
        StaminaService::applyAfterWeek($gameSave, $week);
        app(MoraleService::class)->applyAfterWeek($gameSave, $week);
        app(PromiseService::class)->evaluateForWeek($gameSave, $week);

        // 5. Semaine suivante : la main repasse au joueur du siège 1.
        $gameSave->week = max((int) ($gameSave->week ?? 1), $week + 1);
        $firstHuman = $gameSave->controlledGameTeams()->first();
        if ($firstHuman) {
            $gameSave->controlled_game_team_id = $firstHuman->id;
        }
        $gameSave->save();

        if ($bonusEnabled) {
            app(BonusCardShopService::class)->generateWeeklyOffers($gameSave);
            app(AiBonusCardService::class)->processWeek($gameSave);
        }

        // 6. Coupe du Monde : progression du tournoi au lieu de la fin de saison.
        if ($gameSave->competition_type === 'world_cup') {
            app(TournamentService::class)->advance($gameSave);
            return self::OUTCOME_PLAY;
        }

        // 7. Verdict de la direction (jauge de confiance), puis fin de saison.
        $career = app(CareerObjectiveService::class);
        $career->afterWeek($gameSave, $week);
        if ($career->isGameOver($gameSave)) {
            return self::OUTCOME_GAME_OVER;
        }

        $seasonService = app(SeasonService::class);
        if ($seasonService->isSeasonOver($gameSave)) {
            $seasonService->endSeason($gameSave);
            return self::OUTCOME_SEASON_END;
        }

        return self::OUTCOME_PLAY;
    }

    /**
     * Revenus hebdomadaires de toutes les équipes : base + bonus de résultat.
     * La difficulté du mandat réduit la base des équipes humaines (Ligue).
     */
    public function applyWeeklyIncome(GameSave $gameSave, int $week): void
    {
        $humanBaseIncome = self::BASE_INCOME;
        if ($gameSave->competition_type !== 'world_cup') {
            $preset = CareerObjectiveService::PRESETS[$gameSave->getConfig('career_difficulty', 'standard')] ?? null;
            if ($preset !== null) {
                $humanBaseIncome = $preset['weekly_income'];
            }
        }

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

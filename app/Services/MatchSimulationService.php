<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Support\MatchSimulationResult;

/**
 * « SIMULER MON MATCH » : le manager demande au serveur de jouer à sa place le
 * prochain match de son équipe, au lieu de le jouer à la main.
 *
 * Ne pas confondre avec :
 *   - MatchSimulator      : le moteur qui simule réellement un match ;
 *   - WeekAdvanceService  : simule TOUTE la semaine (matchs des équipes IA).
 *
 * Ce service vérifie que la demande est légitime, fait ajuster la composition de
 * l'adversaire IA, simule le match, verse les primes de défis, puis enchaîne : un autre
 * manager humain doit jouer, ou la semaine est clôturée (WeekAdvanceService).
 *
 * Il renvoie un MatchSimulationResult que le contrôleur traduit en réponse HTTP.
 *
 * IMPORTANT : ce service ne pose PAS de verrou. L'appelant doit tenir le verrou de la
 * sauvegarde (LocksGameSave), qui garantit qu'un double clic ne simule pas le match deux fois.
 */
class MatchSimulationService
{
    public function __construct(
        private CareerObjectiveService $career,
        private AiLineUpService $aiLineUps,
        private MatchSimulator $simulator,
        private SeasonChallengeService $seasonChallenges,
        private WeekAdvanceService $weeks,
    ) {
    }

    /**
     * Simule $match, le prochain match de l'équipe active, puis fait avancer la partie.
     *
     * Sans effet (retour à la page de jeu) si le match n'est plus à jouer : déjà joué ou
     * d'une autre semaine (rejeu d'une demande), partie hors phase de matchs, ou pause de
     * mi-saison en attente. Partie terminée → écran de fin de partie.
     *
     * Interdit si l'équipe active ne joue pas ce match, ou si l'adversaire est aussi
     * dirigé par un manager humain (il doit alors jouer son match lui-même).
     */
    public function simulateNextMatch(GameSave $save, GameMatch $match): MatchSimulationResult
    {
        $match->refresh();

        if ($match->status !== MatchStatus::Scheduled
            || (int) $match->week !== (int) $save->week
            || ! $save->isInMatchPhase()
            || $save->hasPendingMidseasonBreak()) {
            return MatchSimulationResult::redirect(WeekClosingService::OUTCOME_PLAY);
        }

        if ($this->career->isGameOver($save)) {
            return MatchSimulationResult::redirect(WeekClosingService::OUTCOME_GAME_OVER);
        }

        $homeId       = (int) $match->home_team_id;
        $awayId       = (int) $match->away_team_id;
        $activeTeamId = $this->activeTeamId($save);

        if (! $activeTeamId || ! in_array($activeTeamId, [$homeId, $awayId], true)) {
            return MatchSimulationResult::forbidden('Ton équipe ne joue pas ce match.');
        }

        $opponentId = $homeId === $activeTeamId ? $awayId : $homeId;
        if (in_array($opponentId, $save->controlledGameTeamIds(), true)) {
            return MatchSimulationResult::forbidden('Ce match oppose deux managers humains.');
        }

        // L'IA ajuste la composition de l'adversaire (remplace blessés, suspendus, fatigués).
        $this->aiLineUps->adjustLineupsForWeek($save, [$opponentId]);

        $this->simulator->simulateMatchesCollection(collect([$match]), $save);
        $save->refresh(); // la simulation a modifié l'état de la partie
        $this->seasonChallenges->payCompleted($save, [$homeId, $awayId]);

        $summary = "{$match->homeTeam->name} {$match->home_score}–{$match->away_score} {$match->awayTeam->name}";

        return MatchSimulationResult::simulated(
            $this->weeks->advanceAfterMatch($save, (int) $match->week),
            $summary,
        );
    }

    /**
     * Équipe du manager actif (hot-seat), sinon l'équipe propriétaire de la partie.
     * Renvoie 0 si aucune n'est trouvée.
     */
    private function activeTeamId(GameSave $save): int
    {
        $activeTeamId = (int) $save->controlled_game_team_id;

        if (! $activeTeamId && $save->team_id) {
            $activeTeamId = (int) GameTeam::where('game_save_id', $save->id)
                ->where('base_team_id', $save->team_id)
                ->value('id');
        }

        return $activeTeamId;
    }
}

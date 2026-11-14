<?php

namespace App\Services;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;

/**
 * Fait AVANCER le calendrier : simuler la semaine courante, ou enchaîner après un
 * match (joué ou simulé).
 *
 * Deux situations, une même règle de fond — « on ne simule jamais le match d'un
 * manager humain » :
 *
 *   - simulateWeek()       : le manager clique sur « Simuler la semaine » ;
 *   - advanceAfterMatch()  : un match vient de se terminer.
 *
 * Dans les deux cas, si un autre manager humain a encore un match à jouer cette
 * semaine, la main lui passe (hot-seat). Sinon la semaine est clôturée par
 * WeekClosingService (revenus, entraînement IA, transferts, fin de saison…).
 *
 * Chaque méthode renvoie l'ÉCRAN vers lequel rediriger, sous la forme d'une constante
 * WeekClosingService::OUTCOME_* (page de jeu, partie terminée ou fin de saison).
 * Le service ne fabrique aucune réponse HTTP.
 *
 * IMPORTANT : ce service ne pose PAS de verrou. L'appelant doit tenir le verrou de la
 * sauvegarde (LocksGameSave), qui garantit qu'un double clic ne simule pas deux semaines.
 */
class WeekAdvanceService
{
    public function __construct(
        private CareerObjectiveService $career,
        private SeasonService $seasons,
        private WeekClosingService $weekClosing,
    ) {
    }

    /**
     * Simule TOUTE la semaine courante : les matchs de toutes les équipes IA, puis
     * clôture de la semaine.
     *
     * Rien n'est simulé (retour à la page de jeu, ou partie terminée) si :
     *   - la partie n'est pas dans une phase de matchs (draft, fin de saison…) ;
     *   - la carrière est terminée → OUTCOME_GAME_OVER ;
     *   - la semaine reçue n'est plus la semaine courante : c'est un double clic ou un
     *     rejeu, la semaine a déjà avancé (idempotence) ;
     *   - une pause de mi-saison est en attente : on la termine d'abord si le manager l'a
     *     demandé, sinon on ne fait rien ;
     *   - un manager humain a encore un match à jouer : la main lui passe.
     *
     * @param int|null $displayedWeek         semaine affichée par le navigateur (null = non transmise)
     * @param bool     $completeMidseasonBreak vrai si le manager valide la pause de mi-saison
     * @return string une constante WeekClosingService::OUTCOME_*
     */
    public function simulateWeek(GameSave $gameSave, ?int $displayedWeek, bool $completeMidseasonBreak): string
    {
        if (! $gameSave->isInMatchPhase()) {
            return WeekClosingService::OUTCOME_PLAY;
        }

        if ($this->career->isGameOver($gameSave)) {
            return WeekClosingService::OUTCOME_GAME_OVER;
        }

        $week = (int) ($gameSave->week ?? 1);

        // Idempotence : un second envoi (double clic) arrive après l'avancement et ne doit
        // simuler ni la semaine suivante, ni le match que le joueur devait disputer.
        if ($displayedWeek !== null && $displayedWeek !== $week) {
            return WeekClosingService::OUTCOME_PLAY;
        }

        if ($completeMidseasonBreak) {
            $this->seasons->completeMidseasonBreak($gameSave);

            return WeekClosingService::OUTCOME_PLAY;
        }
        if ($gameSave->hasPendingMidseasonBreak()) {
            return WeekClosingService::OUTCOME_PLAY;
        }

        return $this->advance($gameSave, $week);
    }

    /**
     * Étape qui suit un match terminé (joué ou simulé) : passe la main à un autre manager
     * humain s'il lui reste un match, sinon clôture la semaine.
     *
     * @return string une constante WeekClosingService::OUTCOME_*
     */
    public function advanceAfterMatch(GameSave $gameSave, int $week): string
    {
        return $this->advance($gameSave, $week);
    }

    /**
     * Si un manager humain doit encore jouer cette semaine, il devient le manager actif et
     * on revient à la page de jeu ; sinon la semaine est clôturée.
     */
    private function advance(GameSave $gameSave, int $week): string
    {
        $nextHuman = $this->nextHumanWithPendingMatch($gameSave, $week);
        if ($nextHuman) {
            $gameSave->controlled_game_team_id = $nextHuman->id;
            $gameSave->save();

            return WeekClosingService::OUTCOME_PLAY;
        }

        return $this->weekClosing->close($gameSave, $week);
    }

    /**
     * Prochain manager humain (par ordre de siège) dont le match de la semaine n'est pas
     * encore joué, ou null si tous ont joué.
     */
    private function nextHumanWithPendingMatch(GameSave $gameSave, int $week): ?GameTeam
    {
        foreach ($gameSave->controlledGameTeams()->get() as $team) {
            $hasPending = GameMatch::where('game_save_id', $gameSave->id)
                ->where('week', $week)
                ->where('status', 'scheduled')
                ->where(function ($q) use ($team) {
                    $q->where('home_team_id', $team->id)
                        ->orWhere('away_team_id', $team->id);
                })
                ->exists();

            if ($hasPending) {
                return $team;
            }
        }

        return null;
    }
}

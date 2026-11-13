<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;

/**
 * Enregistre le RÉSULTAT d'un match joué À LA MAIN par le manager (le match est
 * déroulé dans le navigateur, puis le résultat est envoyé au serveur).
 *
 * Pour un match SIMULÉ par le serveur, c'est MatchSimulator qui fait le même
 * travail ; ce service en est l'équivalent pour un match joué.
 *
 * record() enchaîne, pour CE match uniquement :
 *   1. score, statistiques et effectifs alignés du match ;
 *   2. classement des deux équipes ;
 *   3. fautes, cartons, suspensions et blessures ;
 *   4. progression des joueurs, alchimie, déclarations, défis de saison.
 *
 * La clôture de la semaine (revenus, matchs des autres équipes…) n'est PAS ici :
 * elle est faite ensuite par WeekClosingService, appelé par le contrôleur.
 *
 * IMPORTANT : ce service ne pose PAS de verrou et ne gère pas de transaction. Il
 * doit être appelé sous le verrou de la sauvegarde (LocksGameSave), qui annule
 * tout en cas d'erreur.
 */
class MatchResultService
{
    /** Rounds de Coupe du Monde où une égalité se joue aux tirs au but. */
    private const KNOCKOUT_ROUNDS = ['semi', 'final'];

    /** Injecte les services appelés lors de l'enregistrement d'un résultat (compositions, blessures, progression, alchimie, déclarations, défis). */
    public function __construct(
        private MatchLineupSnapshot $lineupSnapshot,
        private FoulAndInjuryService $foulsAndInjuries,
        private PostMatchProgressionService $progression,
        private PlayerChemistryService $chemistry,
        private DeclarationService $declarations,
        private SeasonChallengeService $seasonChallenges,
    ) {
    }

    /**
     * Vrai si le match peut encore être enregistré. Faux = on ignore la demande
     * (le match est déjà joué, la partie n'est pas dans une phase de matchs, ou une
     * pause de mi-saison est en attente) : c'est ce qui rend l'envoi idempotent
     * (un double clic ou un rejeu réseau ne recompte rien).
     */
    public function canStillBeRecorded(GameSave $gameSave, GameMatch $match): bool
    {
        return $match->status !== MatchStatus::Played
            && $gameSave->isInMatchPhase()
            && ! $gameSave->hasPendingMidseasonBreak();
    }

    /**
     * Vrai si le match est prévu une semaine FUTURE. L'enregistrer ferait sauter
     * les semaines intermédiaires : l'appelant doit le refuser.
     */
    public function isScheduledForFutureWeek(GameSave $gameSave, GameMatch $match): bool
    {
        return (int) $match->week > (int) ($gameSave->week ?? 1);
    }

    /**
     * Enregistre le résultat du match et toutes ses conséquences.
     *
     * @param  int        $homeScore   buts de l'équipe à domicile
     * @param  int        $awayScore   buts de l'équipe à l'extérieur
     * @param  array|null $matchStats  statistiques complètes envoyées par le moteur de match (null si absentes)
     * @param  array      $foulEvents  fautes, cartons et blessures survenus pendant le match
     */
    public function record(GameSave $gameSave, GameMatch $match, int $homeScore, int $awayScore, ?array $matchStats, array $foulEvents): void
    {
        $homeTeamId = (int) $match->home_team_id;
        $awayTeamId = (int) $match->away_team_id;

        // 1. Le match lui-même : score, statistiques, statut « joué ».
        $this->saveMatch($match, $homeScore, $awayScore, $matchStats);

        // 2. Classement (victoires, nuls, défaites, buts).
        $this->updateStandings($gameSave, $homeTeamId, $awayTeamId, $homeScore, $awayScore);

        // 3. Fautes, cartons et blessures de ce match.
        $this->foulsAndInjuries->processMatchEvents($gameSave, $match, $foulEvents);

        // 4. Conséquences sur les joueurs et la partie.
        $this->progression->applyForMatch($gameSave, $match);
        $this->chemistry->recordMatch($gameSave, $match);
        $gameSave->refresh(); // la progression et l'alchimie ont modifié l'état de la partie
        $this->declarations->consumeMatchBoosts($gameSave, $match->id);
        $this->seasonChallenges->payCompleted($gameSave, [$homeTeamId, $awayTeamId]);
    }

    /**
     * Écrit le score et les statistiques du match, et le marque « joué ».
     *
     * Deux nettoyages sur les statistiques reçues du navigateur :
     *  - tirs au but : conservés seulement pour une égalité en phase finale, avec
     *    un vainqueur qui joue bien ce match ;
     *  - effectifs : on remplace par un instantané fait côté serveur, pour que
     *    l'historique reste juste même après des transferts.
     */
    private function saveMatch(GameMatch $match, int $homeScore, int $awayScore, ?array $stats): void
    {
        $homeTeamId = (int) $match->home_team_id;
        $awayTeamId = (int) $match->away_team_id;

        if (is_array($stats) && array_key_exists('shootout', $stats)) {
            $winner = (int) (($stats['shootout']['winner_team_id'] ?? 0));

            $isValidShootout = in_array($match->round, self::KNOCKOUT_ROUNDS, true)
                && $homeScore === $awayScore
                && in_array($winner, [$homeTeamId, $awayTeamId], true);

            if (! $isValidShootout) {
                unset($stats['shootout']);
            }
        }

        if (is_array($stats)) {
            unset($stats['chemistry_recorded']); // drapeau interne, jamais fourni par le client
            $stats['lineups'] = $this->lineupSnapshot->build($match);
        }

        $match->home_score  = $homeScore;
        $match->away_score  = $awayScore;
        $match->status      = MatchStatus::Played;
        $match->match_stats = $stats;
        $match->save();
    }

    /**
     * Met à jour le classement des deux équipes : une victoire, un nul ou une
     * défaite chacune, et les buts marqués / encaissés.
     */
    private function updateStandings(GameSave $gameSave, int $homeTeamId, int $awayTeamId, int $homeScore, int $awayScore): void
    {
        $home = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($homeTeamId);
        $away = GameTeam::where('game_save_id', $gameSave->id)->findOrFail($awayTeamId);

        if ($homeScore > $awayScore) {
            $home->wins++;
            $away->losses++;
        } elseif ($homeScore < $awayScore) {
            $away->wins++;
            $home->losses++;
        } else {
            $home->draws++;
            $away->draws++;
        }

        $home->goals_for     += $homeScore;
        $home->goals_against += $awayScore;
        $away->goals_for     += $awayScore;
        $away->goals_against += $homeScore;

        $home->save();
        $away->save();
    }
}

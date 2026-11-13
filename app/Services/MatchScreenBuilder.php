<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Closure;

/**
 * Prépare l'ÉCRAN DE MATCH jouable (page « Match/Engine ») : choisit le match à
 * jouer, remet les relances de capitaine à zéro, fait ajuster la composition de
 * l'adversaire IA, puis construit la configuration complète du moteur de match
 * (équipes, effectifs, formations, classement, réglages de la partie).
 *
 * Le moteur de match lui-même tourne dans le navigateur (resources/js/Pages/Match) :
 * ce service ne joue rien, il lui fournit ses données de départ.
 *
 * Les étapes sont séparées car le contrôleur doit poser le verrou de la partie
 * autour de l'une d'elles (adjustOpponentLineups) : un service ne peut pas le faire.
 *
 * Étapes, dans l'ordre d'appel par GameMatchController::match() :
 *   1. activeTeam()               équipe jouée par le manager actif ;
 *   2. nextMatch()                son prochain match à jouer ;
 *   3. resetCaptainRerolls()      3 relances de capitaine pour chaque camp ;
 *   4. adjustOpponentLineups()    composition IA du jour (sous verrou) ;
 *   5. engineConfig()             configuration envoyée au moteur.
 */
class MatchScreenBuilder
{
    /** Nombre de relances de capitaine accordées à chaque match. */
    private const CAPTAIN_REROLLS = 3;

    /** Rounds de Coupe du Monde où une égalité se joue aux tirs au but. */
    private const KNOCKOUT_ROUNDS = ['semi', 'final'];

    public function __construct(
        private AiLineUpService $aiLineUps,
        private MatchRosterPresenter $rosters,
        private SeasonService $seasons,
    ) {
    }

    // ──────────────────────────────────────────────────────────────
    //   1-2. QUEL MATCH JOUER ?
    // ──────────────────────────────────────────────────────────────

    /**
     * Équipe jouée sur cet écran : celle du manager ACTIF (hot-seat), sinon l'équipe
     * propriétaire de la partie. Échoue (404) si aucune des deux n'existe.
     */
    public function activeTeam(GameSave $gameSave): GameTeam
    {
        $active = $gameSave->controlled_game_team_id
            ? GameTeam::where('game_save_id', $gameSave->id)->find($gameSave->controlled_game_team_id)
            : null;

        return $active
            ?? GameTeam::where('game_save_id', $gameSave->id)
                ->where('base_team_id', $gameSave->team_id)
                ->firstOrFail();
    }

    /**
     * Prochain match à jouer de l'équipe (le plus proche, cette semaine ou plus tard),
     * avec les deux équipes et leurs contrats ACTIFS cette semaine : ni contrat
     * expiré, ni pré-contrat futur sur la feuille de match (comme la page de jeu).
     * Échoue (404) s'il n'y a plus de match à jouer.
     */
    public function nextMatch(GameSave $gameSave, GameTeam $team, int $week): GameMatch
    {
        $activeContracts = $this->activeContractsEagerLoad($week);

        return GameMatch::with([
            'homeTeam.contracts' => $activeContracts,
            'awayTeam.contracts' => $activeContracts,
        ])
            ->where('game_save_id', $gameSave->id)
            ->where('status', 'scheduled')
            ->where('week', '>=', $week)
            ->where(function ($q) use ($team) {
                $q->where('home_team_id', $team->id)->orWhere('away_team_id', $team->id);
            })
            ->orderBy('week')
            ->firstOrFail();
    }

    // ──────────────────────────────────────────────────────────────
    //   3-4. PRÉPARATION AVANT LE COUP D'ENVOI
    // ──────────────────────────────────────────────────────────────

    /**
     * Remet à 3 les relances de capitaine des deux équipes du match. Pendant le match,
     * le moteur les décompte côté serveur (GameMatchController::useCaptainReroll).
     */
    public function resetCaptainRerolls(GameSave $gameSave, GameMatch $match): void
    {
        GameContract::where('game_save_id', $gameSave->id)
            ->whereIn('game_team_id', [$match->home_team_id, $match->away_team_id])
            ->where('is_captain', true)
            ->update([
                'captain_rerolls_remaining'       => self::CAPTAIN_REROLLS,
                'captain_reroll_used_this_action' => false,
            ]);
    }

    /**
     * Fait ajuster par l'IA la composition des deux équipes du match (remplace
     * blessés, suspendus et joueurs trop fatigués) avant le coup d'envoi.
     *
     * À appeler SOUS LE VERROU de la partie (c'est une écriture). Ne fait rien si,
     * une fois le verrou obtenu, la semaine a changé, une pause de mi-saison est en
     * attente ou le match n'est plus à jouer (un autre onglet est passé avant).
     */
    public function adjustOpponentLineups(GameSave $lockedSave, GameMatch $match, int $week): void
    {
        if ((int) $lockedSave->week !== $week || $lockedSave->hasPendingMidseasonBreak()) {
            return;
        }
        if ($match->fresh()?->status !== MatchStatus::Scheduled) {
            return;
        }

        $this->aiLineUps->adjustLineupsForWeek($lockedSave, [$match->home_team_id, $match->away_team_id]);
    }

    // ──────────────────────────────────────────────────────────────
    //   5. CONFIGURATION DU MOTEUR
    // ──────────────────────────────────────────────────────────────

    /**
     * Configuration complète envoyée au moteur de match du navigateur.
     *
     * @param string $requestedControlMode « single » (un seul camp joué) ou « both » (les deux camps
     *                                     à la main) tel que demandé dans l'URL ; tout autre valeur
     *                                     est ramenée à « single ». Un derby entre deux managers humains
     *                                     force « both » (l'IA ne jouerait pas l'équipe du second humain).
     */
    public function engineConfig(GameSave $gameSave, GameMatch $match, GameTeam $activeTeam, string $requestedControlMode): array
    {
        // La composition vient peut-être d'être ajustée : on recharge les effectifs à jour.
        $activeContracts = $this->activeContractsEagerLoad((int) $match->week);
        $match->load(['homeTeam.contracts' => $activeContracts, 'awayTeam.contracts' => $activeContracts]);

        $homeTeam = $match->homeTeam;
        $awayTeam = $match->awayTeam;

        // « Interne » = le camp du manager actif, « externe » = son adversaire.
        $isControlledHome = (int) $activeTeam->id === (int) $homeTeam->id;
        $internalTeam     = $isControlledHome ? $homeTeam : $awayTeam;
        $externalTeam     = $isControlledHome ? $awayTeam : $homeTeam;

        $ranks = $this->standingsRanks($gameSave);

        return [
            'gameSaveId'       => $gameSave->id,
            'matchId'          => $match->id,
            'week'             => $match->week,
            'maxTurns'         => (int) $gameSave->getConfig('match_max_turns', 30),
            'criticalsEnabled' => (bool) $gameSave->getConfig('match_criticals_enabled'),
            'maxSubstitutions' => (int) $gameSave->getConfig('match_max_substitutions'),
            // Coupe du Monde, phase finale : une égalité se joue aux tirs au but dans le moteur.
            'isKnockout'       => in_array($match->round, self::KNOCKOUT_ROUNDS, true),
            'controlMode'      => $this->controlMode($gameSave, $homeTeam, $awayTeam, $requestedControlMode),
            'controlledSide'   => 'internal',

            'homeTeamName'     => $homeTeam->name,
            'awayTeamName'     => $awayTeam->name,
            'homeLogoUrl'      => $this->logoUrl($homeTeam),
            'awayLogoUrl'      => $this->logoUrl($awayTeam),
            'isControlledHome' => $isControlledHome,

            'homeTeamId'       => $match->home_team_id,
            'internalIsHome'   => $internalTeam->id === $match->home_team_id,
            'awayTeamId'       => $match->away_team_id,

            'sides' => [
                'internalTeamId' => $internalTeam->id,
                'externalTeamId' => $externalTeam->id,
            ],

            'teams' => [
                'internal' => $this->teamPayload($internalTeam, $gameSave, $match, $ranks),
                'external' => $this->teamPayload($externalTeam, $gameSave, $match, $ranks),
            ],
        ];
    }

    /**
     * Mode de contrôle du match. Par défaut un seul camp est joué à la main ; « both »
     * est possible sur demande, et imposé pour un derby entre deux managers humains
     * (hot-seat), sinon l'IA jouerait l'équipe du second humain.
     */
    private function controlMode(GameSave $gameSave, GameTeam $homeTeam, GameTeam $awayTeam, string $requested): string
    {
        $mode = in_array($requested, ['both', 'single'], true) ? $requested : 'single';

        $humanIds = array_map('intval', $gameSave->controlledGameTeamIds());
        if (in_array((int) $homeTeam->id, $humanIds, true) && in_array((int) $awayTeam->id, $humanIds, true)) {
            return 'both';
        }

        return $mode;
    }

    /**
     * Données d'une équipe pour le moteur : identité, rang au classement, formation,
     * style tactique, philosophie de management et effectif (titulaires puis remplaçants).
     */
    private function teamPayload(GameTeam $team, GameSave $gameSave, GameMatch $match, $ranks): array
    {
        return [
            'id'                   => $team->id,
            'name'                 => $team->name,
            'description'          => $team->description,
            'rank'                 => $ranks->get($team->id),
            'logo_path'            => $team->logo_path,
            'formation'            => $team->formation ?? '3-2-3-2',
            'tacticalStyle'        => $team->tactical_style,
            'managementPhilosophy' => $team->management_philosophy,
            'players'              => $this->rosters->forTeam($team, $gameSave, $match->id),
        ];
    }

    /** Rang de chaque équipe au classement actuel (1 = leader), indexé par id d'équipe. */
    private function standingsRanks(GameSave $gameSave)
    {
        return $this->seasons->sortStandings(
            GameTeam::where('game_save_id', $gameSave->id)->get()
        )->pluck('id')->flip()->map(fn ($index) => $index + 1);
    }

    /** URL publique du logo d'une équipe, ou null si elle n'en a pas. */
    private function logoUrl(GameTeam $team): ?string
    {
        return $team->logo_path ? '/' . ltrim($team->logo_path, '/') : null;
    }

    /** Chargement des contrats actifs à une semaine donnée (avec le joueur et sa fiche de référence). */
    private function activeContractsEagerLoad(int $week): Closure
    {
        return fn ($q) => $q->activeAt($week)->with('gamePlayer.basePlayer');
    }
}

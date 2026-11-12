<?php

namespace App\Services;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GamePromise;
use App\Models\GameSaves\GameSanction;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use Illuminate\Support\Facades\DB;

class SeasonService
{
    public const RANK_PRIZE_BASE = 6000;
    public const RANK_PRIZE_STEP = 400;
    public const MIDSEASON_RECOVERY = 20;

    /**
     * Nombre de semaines d'une saison aller-retour pour N équipes. Avec un
     * nombre impair, une équipe fictive « repos » s'ajoute : N journées par
     * phase au lieu de N-1. Seule source de vérité (création, draft, IA).
     */
    public static function lengthForTeamCount(int $teamCount): int
    {
        if ($teamCount < 2) return 28;
        return $teamCount % 2 === 1 ? $teamCount * 2 : ($teamCount - 1) * 2;
    }

    public function getSeasonLength(GameSave $gameSave): int
    {
        return self::lengthForTeamCount(GameTeam::where('game_save_id', $gameSave->id)->count());
    }

    public function isSeasonOver(GameSave $gameSave): bool
    {
        return ($gameSave->week ?? 1) > $this->getSeasonLength($gameSave);
    }

    public function scheduleMidseasonBreak(GameSave $gameSave, int $closedWeek): void
    {
        if ($gameSave->competition_type === 'world_cup' || $closedWeek * 2 !== $this->getSeasonLength($gameSave)) {
            return;
        }

        $state = $gameSave->state ?? [];
        $state['midseason_break'] ??= ['return_week' => $closedWeek + 1, 'completed' => false];
        $gameSave->state = $state;
    }

    public function completeMidseasonBreak(GameSave $gameSave): void
    {
        if (!$gameSave->hasPendingMidseasonBreak()) return;

        $recovery = self::MIDSEASON_RECOVERY;
        GamePlayer::where('game_save_id', $gameSave->id)
            ->where('stamina', '<', 100)
            ->update(['stamina' => DB::raw("CASE WHEN stamina + {$recovery} > 100 THEN 100 ELSE stamina + {$recovery} END")]);

        $state = $gameSave->state;
        $state['midseason_break']['completed'] = true;
        $gameSave->state = $state;
        $gameSave->save();
    }

    /**
     * Clôture la saison : calcule classement final, MVP, primes,
     * sauvegarde le récap dans le state, et passe en phase 'season_end'.
     */
    public function endSeason(GameSave $gameSave): array
    {
        $teams = GameTeam::where('game_save_id', $gameSave->id)->get();

        $standings = $this->sortStandings($teams);
        $champion  = $standings->first();
        $mvp       = $this->findSeasonMvp($gameSave);

        // Défis de saison : évalués AVANT les primes de classement (objectif budget).
        $challenges = app(SeasonChallengeService::class)->evaluateSeasonEnd($gameSave, $standings);

        $standingsRecap = [];
        foreach ($standings as $i => $team) {
            $rank  = $i + 1;
            $prize = max(0, self::RANK_PRIZE_BASE - ($rank - 1) * self::RANK_PRIZE_STEP);

            $team->budget = ($team->budget ?? 0) + $prize;
            $team->save();

            $gf = (int) ($team->goals_for     ?? 0);
            $ga = (int) ($team->goals_against ?? 0);

            $standingsRecap[] = [
                'rank'          => $rank,
                'team_id'       => $team->id,
                'name'          => $team->name,
                'logo_path'     => $team->logo_path,
                'wins'          => $team->wins,
                'draws'         => $team->draws,
                'losses'        => $team->losses,
                'points'        => $team->wins * 3 + $team->draws,
                'goals_for'     => $gf,
                'goals_against' => $ga,
                'goal_diff'     => $gf - $ga,
                'prize'         => $prize,
            ];
        }

        // Joueurs de l'équipe contrôlée en rupture : ils ne re-signeront pas
        // et refuseront d'être draftés la saison prochaine.
        $transferRequests = [];
        $controlledTeamId = (int) ($gameSave->controlled_game_team_id ?? 0);
        if ($controlledTeamId) {
            $playerIds = GameContract::where('game_save_id', $gameSave->id)
                ->where('game_team_id', $controlledTeamId)
                ->pluck('game_player_id');

            foreach (GamePlayer::whereIn('id', $playerIds)->get() as $p) {
                if (!MoraleService::refusesToSign($p)) continue;

                $transferRequests[] = [
                    'player_id'  => $p->id,
                    'name'       => $p->full_name,
                    'photo_path' => $p->photo_path,
                    'morale'     => (int) $p->morale,
                    'affinity'   => (int) $p->coach_affinity,
                    'reason'     => (int) $p->coach_affinity <= MoraleService::AFFINITY_REFUSAL_THRESHOLD
                        ? 'coach'   // fâché contre toi
                        : 'morale', // révolté contre le club
                ];
            }
        }

        // Verdict de la direction (objectif de classement, confiance, licenciement).
        $careerVerdict = app(CareerObjectiveService::class)->evaluateSeasonEnd($gameSave, $standings);

        $recap = [
            'season'    => $gameSave->season,
            'champion'  => $champion ? [
                'id'        => $champion->id,
                'name'      => $champion->name,
                'logo_path' => $champion->logo_path,
            ] : null,
            'mvp'       => $mvp,
            'standings' => $standingsRecap,
            'transfer_requests' => $transferRequests,
            'career_verdict'    => $careerVerdict,
            'challenges'        => $challenges,
        ];

        $state = $gameSave->state ?? [];
        $state['season_history']      = $state['season_history'] ?? [];
        $state['season_history'][$gameSave->season] = $recap;
        $state['last_season_recap']   = $recap;

        $gameSave->state = $state;
        $gameSave->phase = 'season_end';
        $gameSave->save();

        return $recap;
    }

    /**
     * Détermine le MVP de la saison : meilleur buteur (départage par duels gagnés).
     */
    private function findSeasonMvp(GameSave $gameSave): ?array
    {
        $stats = PlayerStatsService::aggregateForSave($gameSave);
        if (empty($stats)) return null;

        $bestId    = null;
        $bestScore = -1;
        $bestStats = null;

        foreach ($stats as $pid => $s) {
            $goals = $s['offense']['goals'] ?? 0;
            $score = $goals * 10 + ($s['duelsWon'] ?? 0);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId    = (int) $pid;
                $bestStats = $s;
            }
        }

        if (!$bestId) return null;

        $player = GamePlayer::where('game_save_id', $gameSave->id)->find($bestId);
        if (!$player) return null;

        $contract = $player->contracts()->with('gameTeam')->first();

        return [
            'player_id'  => $player->id,
            'name'       => trim($player->firstname . ' ' . $player->lastname),
            'position'   => $player->position,
            'photo_path' => $player->photo_path,
            'goals'      => $bestStats['offense']['goals'] ?? 0,
            'duels_won'  => $bestStats['duelsWon'] ?? 0,
            'team_name'  => $contract?->gameTeam?->name,
        ];
    }

    /**
     * Démarre la saison suivante : reset du classement, expiration des contrats,
     * suppression du calendrier, et lancement d'une nouvelle draft dont l'ordre
     * est l'inverse du classement final (le dernier de la saison pioche en premier).
     */
    public function startNewSeason(GameSave $gameSave): void
    {
        $teams = GameTeam::where('game_save_id', $gameSave->id)->get();

        $standings  = $this->sortStandings($teams);
        $draftOrder = $standings->reverse()->pluck('id')->values()->all();

        foreach ($teams as $team) {
            $team->wins          = 0;
            $team->draws         = 0;
            $team->losses        = 0;
            $team->goals_for     = 0;
            $team->goals_against = 0;
            $team->save();
        }

        // Tous les contrats expirent : les joueurs redeviennent libres pour la draft.
        // Les contrats, matchs, blessures et sanctions ne sont plus supprimés : ils
        // restent rattachés à leur saison (historique) et sortent du filtre de
        // saison courante dès que `season` est incrémenté plus bas.
        GamePlayer::where('game_save_id', $gameSave->id)->select('id')->chunkById(250, function ($players) use ($gameSave) {
            foreach ($players as $player) {
                GamePlayer::whereKey($player->id)->update(['number' => null, 'luck' => $gameSave->rollPlayerLuck()]);
            }
        });

        // La semaine repart à 1 : tout ce qui est indexé sur la semaine ne doit
        // pas déborder sur la nouvelle saison (un blessé de fin de saison serait
        // sinon absent jusqu'à la même semaine de la saison suivante, et les
        // jaunes se cumuleraient d'une saison à l'autre). L'intersaison soigne
        // et purge les sanctions ; les promesses en cours deviennent caduques
        // (sans effet sur le moral : leur fenêtre a disparu avec le calendrier).
        // Formations de poste en cours : terminées pendant l'intersaison.
        app(PositionTrainingService::class)->completeDue($gameSave, 0, all: true);
        GamePromise::where('game_save_id', $gameSave->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $gameSave->season = ($gameSave->season ?? 1) + 1;
        $gameSave->week   = 1;
        $gameSave->phase  = 'intersaison_draft';

        $state = $gameSave->state ?? [];
        unset($state['lineup'], $state['declaration_match_boosts'], $state['midseason_break']);
        // Tous les contrats ont expiré : les signatures d'agents libres de la
        // saison passée n'ont plus lieu d'être (et bornent la taille du state).
        unset($state['free_agent_signings']);
        $gameSave->state = $state;
        $gameSave->save();

        // Logs de moral et déclarations : on garde la saison qui vient de finir
        // (l'onglet Moral en affiche les derniers événements) et on purge les plus
        // anciennes, qui ne sont plus lues et grossissent à chaque semaine.
        self::pruneOldLogs($gameSave);

        // Nouveau mandat de la direction pour la saison à venir.
        app(CareerObjectiveService::class)->resetMandateForNewSeason($gameSave);

        app(DraftService::class)->initDraft($gameSave, $draftOrder);
    }

    /** Supprime logs de moral et déclarations antérieurs à la saison précédente. */
    public static function pruneOldLogs(GameSave $gameSave): void
    {
        $oldest = (int) $gameSave->season - 1;

        \App\Models\GameSaves\GamePlayerMoraleLog::where('game_save_id', $gameSave->id)->where('season', '<', $oldest)->delete();
        \App\Models\GameSaves\GameDeclaration::where('game_save_id', $gameSave->id)->where('season', '<', $oldest)->delete();
    }

    /**
     * Trie les équipes selon les règles de classement :
     * points → différence de buts → buts marqués → victoires → nom.
     * Les buts sont lus directement sur game_teams (maintenus à la simulation).
     *
     * @param  \Illuminate\Support\Collection<int, GameTeam>  $teams
     * @return \Illuminate\Support\Collection<int, GameTeam>
     */
    public function sortStandings($teams)
    {
        return $teams->sort(function ($a, $b) {
            $pa = ($a->wins ?? 0) * 3 + ($a->draws ?? 0);
            $pb = ($b->wins ?? 0) * 3 + ($b->draws ?? 0);
            if ($pa !== $pb) return $pb <=> $pa;

            $da = ($a->goals_for ?? 0) - ($a->goals_against ?? 0);
            $db = ($b->goals_for ?? 0) - ($b->goals_against ?? 0);
            if ($da !== $db) return $db <=> $da;

            $fa = $a->goals_for ?? 0;
            $fb = $b->goals_for ?? 0;
            if ($fa !== $fb) return $fb <=> $fa;

            if (($a->wins ?? 0) !== ($b->wins ?? 0)) return ($b->wins ?? 0) <=> ($a->wins ?? 0);

            return strcmp((string) $a->name, (string) $b->name);
        })->values();
    }
}

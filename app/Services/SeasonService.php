<?php

namespace App\Services;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;

class SeasonService
{
    public const RANK_PRIZE_BASE = 6000;
    public const RANK_PRIZE_STEP = 400;

    public function getSeasonLength(GameSave $gameSave): int
    {
        $teamCount = GameTeam::where('game_save_id', $gameSave->id)->count();
        if ($teamCount < 2) return 28;
        return $teamCount % 2 === 1 ? $teamCount * 2 : ($teamCount - 1) * 2;
    }

    public function isSeasonOver(GameSave $gameSave): bool
    {
        return ($gameSave->week ?? 1) > $this->getSeasonLength($gameSave);
    }

    /**
     * Clôture la saison : calcule classement final, MVP, primes,
     * sauvegarde le récap dans le state, et passe en phase 'season_end'.
     */
    public function endSeason(GameSave $gameSave): array
    {
        $teams = GameTeam::where('game_save_id', $gameSave->id)->get();

        [$goalsFor, $goalsAgainst] = $this->aggregateGoals($gameSave);
        $standings = $this->sortStandings($teams, $goalsFor, $goalsAgainst);
        $champion  = $standings->first();
        $mvp       = $this->findSeasonMvp($gameSave);

        $standingsRecap = [];
        foreach ($standings as $i => $team) {
            $rank  = $i + 1;
            $prize = max(0, self::RANK_PRIZE_BASE - ($rank - 1) * self::RANK_PRIZE_STEP);

            $team->budget = ($team->budget ?? 0) + $prize;
            $team->save();

            $gf = $goalsFor[$team->id]     ?? 0;
            $ga = $goalsAgainst[$team->id] ?? 0;

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

        // Agrégé avant la suppression du calendrier (les matchs sont effacés plus bas).
        [$goalsFor, $goalsAgainst] = $this->aggregateGoals($gameSave);
        $standings  = $this->sortStandings($teams, $goalsFor, $goalsAgainst);
        $draftOrder = $standings->reverse()->pluck('id')->values()->all();

        foreach ($teams as $team) {
            $team->wins   = 0;
            $team->draws  = 0;
            $team->losses = 0;
            $team->save();
        }

        // Tous les contrats expirent : les joueurs redeviennent libres pour la draft
        GameContract::where('game_save_id', $gameSave->id)->delete();
        GamePlayer::where('game_save_id', $gameSave->id)->update(['number' => null]);

        GameMatch::where('game_save_id', $gameSave->id)->delete();

        $gameSave->season = ($gameSave->season ?? 1) + 1;
        $gameSave->week   = 1;
        $gameSave->phase  = 'intersaison_draft';

        $state = $gameSave->state ?? [];
        unset($state['lineup']);
        $gameSave->state = $state;
        $gameSave->save();

        app(DraftService::class)->initDraft($gameSave, $draftOrder);
    }

    /**
     * Trie les équipes selon les règles de classement :
     * points → différence de buts → buts marqués → victoires → nom.
     *
     * @param  \Illuminate\Support\Collection<int, GameTeam>  $teams
     * @param  array<int, int>  $goalsFor
     * @param  array<int, int>  $goalsAgainst
     * @return \Illuminate\Support\Collection<int, GameTeam>
     */
    private function sortStandings($teams, array $goalsFor, array $goalsAgainst)
    {
        return $teams->sort(function ($a, $b) use ($goalsFor, $goalsAgainst) {
            $pa = ($a->wins ?? 0) * 3 + ($a->draws ?? 0);
            $pb = ($b->wins ?? 0) * 3 + ($b->draws ?? 0);
            if ($pa !== $pb) return $pb <=> $pa;

            $da = ($goalsFor[$a->id] ?? 0) - ($goalsAgainst[$a->id] ?? 0);
            $db = ($goalsFor[$b->id] ?? 0) - ($goalsAgainst[$b->id] ?? 0);
            if ($da !== $db) return $db <=> $da;

            $fa = $goalsFor[$a->id] ?? 0;
            $fb = $goalsFor[$b->id] ?? 0;
            if ($fa !== $fb) return $fb <=> $fa;

            if (($a->wins ?? 0) !== ($b->wins ?? 0)) return ($b->wins ?? 0) <=> ($a->wins ?? 0);

            return strcmp((string) $a->name, (string) $b->name);
        })->values();
    }

    /**
     * Agrège buts marqués / encaissés par équipe depuis les matchs joués.
     *
     * @return array{0: array<int, int>, 1: array<int, int>} [goalsFor, goalsAgainst]
     */
    private function aggregateGoals(GameSave $gameSave): array
    {
        $goalsFor = [];
        $goalsAgainst = [];

        $matches = GameMatch::where('game_save_id', $gameSave->id)
            ->where('status', 'played')
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->get(['home_team_id', 'away_team_id', 'home_score', 'away_score']);

        foreach ($matches as $m) {
            $goalsFor[$m->home_team_id]     = ($goalsFor[$m->home_team_id]     ?? 0) + $m->home_score;
            $goalsAgainst[$m->home_team_id] = ($goalsAgainst[$m->home_team_id] ?? 0) + $m->away_score;
            $goalsFor[$m->away_team_id]     = ($goalsFor[$m->away_team_id]     ?? 0) + $m->away_score;
            $goalsAgainst[$m->away_team_id] = ($goalsAgainst[$m->away_team_id] ?? 0) + $m->home_score;
        }

        return [$goalsFor, $goalsAgainst];
    }
}

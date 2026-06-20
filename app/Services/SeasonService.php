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

        $standings = $this->sortStandings($teams);
        $champion  = $standings->first();
        $mvp       = $this->findSeasonMvp($gameSave);

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
     * Les buts sont lus directement sur game_teams (maintenus à la simulation).
     *
     * @param  \Illuminate\Support\Collection<int, GameTeam>  $teams
     * @return \Illuminate\Support\Collection<int, GameTeam>
     */
    private function sortStandings($teams)
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

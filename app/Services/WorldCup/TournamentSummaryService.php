<?php

namespace App\Services\WorldCup;

use App\Enums\MatchStatus;
use App\Enums\Nationality;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameMatchPlayerStat;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\Nation;

/**
 * Bilan d'une Coupe du Monde terminée, pour l'écran de fin de tournoi.
 *
 * Domaine : Coupe du Monde. Appelé par GameSeasonController::tournamentEnd. Lecture seule : tout est
 * calculé depuis les matchs joués et `state['tournament']` (via TournamentService::presentation).
 * Contenu : podium, parcours de chaque manager humain, meilleurs buteurs (matchs joués à la main : les
 * matchs simulés n'enregistrent pas de statistiques de joueur) et chiffres clés.
 */
class TournamentSummaryService
{
    /** Buteurs affichés au classement. */
    public const TOP_SCORERS = 5;

    /** Libellé du dernier tour atteint par une équipe éliminée. */
    private const EXIT_LABELS = [
        'group' => 'Éliminé en poules',
        'quarter' => 'Éliminé en quart de finale',
        'semi' => 'Éliminé en demi-finale',
        'final' => 'Finaliste',
    ];

    public function __construct(private TournamentService $tournament) {}

    /**
     * @return array<string, mixed>|null null tant que le tournoi n'est pas terminé
     */
    public function summary(GameSave $gameSave): ?array
    {
        $presentation = $this->tournament->presentation($gameSave);
        if (! $presentation || ($presentation['stage'] ?? null) !== 'done') {
            return null;
        }

        $teams = GameTeam::where('game_save_id', $gameSave->id)->get()->keyBy('id');
        $flagUrls = Nation::flagUrls();
        $matches = GameMatch::where('game_save_id', $gameSave->id)->where('status', MatchStatus::Played)->orderBy('week')->orderBy('id')->get();

        return [
            'champion' => $presentation['champion'],
            'podium' => $this->podium($presentation),
            'managers' => $this->managers($gameSave, $presentation, $teams, $matches, $flagUrls),
            'scorers' => $this->scorers($gameSave, $matches, $teams, $flagUrls),
            'figures' => $this->figures($matches, $teams),
        ];
    }

    /**
     * Les quatre premières places : champion, finaliste, 3e et 4e (match pour la 3e place).
     *
     * @return array<int, array{rank:int, team_id:int, name:string, flag:string}>
     */
    private function podium(array $presentation): array
    {
        $final = $presentation['bracket']['final'][0] ?? null;
        $third = $presentation['bracket']['third'][0] ?? null;
        $ids = [
            1 => $presentation['champion']['team_id'] ?? null,
            2 => $final ? $this->other($final, $presentation['champion']['team_id'] ?? null) : null,
            3 => $third['winner_team_id'] ?? null,
            4 => $third ? $this->other($third, $third['winner_team_id'] ?? null) : null,
        ];

        $refs = collect($presentation['bracket'])->flatten(1)->flatMap(fn ($m) => [$m['home'], $m['away']])->filter()->keyBy('team_id');
        $podium = [];
        foreach ($ids as $rank => $id) {
            if ($id && isset($refs[$id])) {
                $podium[] = ['rank' => $rank] + $refs[$id];
            }
        }

        return $podium;
    }

    /** Id de l'équipe d'un match de bracket autre que $teamId. */
    private function other(array $match, ?int $teamId): ?int
    {
        $home = $match['home']['team_id'] ?? null;
        $away = $match['away']['team_id'] ?? null;

        return $home === $teamId ? $away : $home;
    }

    /**
     * Parcours de chaque manager humain : résultat final et détail de ses matchs.
     *
     * @return array<int, array<string, mixed>>
     */
    private function managers(GameSave $gameSave, array $presentation, $teams, $matches, array $flagUrls): array
    {
        $out = [];
        foreach ($gameSave->controlledGameTeams()->get() as $team) {
            $id = (int) $team->id;
            $mine = $matches->filter(fn (GameMatch $m) => (int) $m->home_team_id === $id || (int) $m->away_team_id === $id);
            $wins = $draws = $losses = $gf = $ga = 0;
            $games = [];
            foreach ($mine as $m) {
                $home = (int) $m->home_team_id === $id;
                $for = (int) ($home ? $m->home_score : $m->away_score);
                $against = (int) ($home ? $m->away_score : $m->home_score);
                $gf += $for;
                $ga += $against;
                $for > $against ? $wins++ : ($for < $against ? $losses++ : $draws++);
                $oppId = $home ? $m->away_team_id : $m->home_team_id;
                $games[] = [
                    'round' => $m->round,
                    'opponent' => $teams[$oppId]->name ?? '?',
                    'flag' => Nationality::flag($teams[$oppId]->name ?? ''),
                    'flag_url' => $flagUrls[$teams[$oppId]->name ?? ''] ?? null,
                    'for' => $for,
                    'against' => $against,
                    'shootout' => ($m->match_stats['shootout']['winner_team_id'] ?? null) !== null && $for === $against
                        ? (int) $m->match_stats['shootout']['winner_team_id'] === $id
                        : null,
                ];
            }

            $out[] = [
                'team_id' => $id,
                'name' => $team->name,
                'flag' => Nationality::flag($team->name),
                'flag_url' => $flagUrls[$team->name] ?? null,
                'seat' => (int) $team->human_seat,
                'result' => $this->resultLabel($id, $presentation),
                'is_champion' => ($presentation['champion']['team_id'] ?? null) === $id,
                'record' => ['played' => $mine->count(), 'wins' => $wins, 'draws' => $draws, 'losses' => $losses, 'gf' => $gf, 'ga' => $ga],
                'games' => $games,
            ];
        }

        return $out;
    }

    /** « Champion du monde », « Médaille de bronze » ou le tour d'élimination. */
    private function resultLabel(int $teamId, array $presentation): string
    {
        if (($presentation['champion']['team_id'] ?? null) === $teamId) {
            return 'Champion du monde';
        }
        if (($presentation['bracket']['third'][0]['winner_team_id'] ?? null) === $teamId) {
            return 'Médaille de bronze';
        }

        return self::EXIT_LABELS[$presentation['eliminated'][$teamId] ?? ''] ?? 'Tournoi terminé';
    }

    /**
     * Meilleurs buteurs du tournoi (statistiques des matchs joués à la main).
     *
     * @return array<int, array{name:string, team:string, flag:string, goals:int}>
     */
    private function scorers(GameSave $gameSave, $matches, $teams, array $flagUrls): array
    {
        $rows = GameMatchPlayerStat::where('game_save_id', $gameSave->id)
            ->whereIn('game_match_id', $matches->pluck('id'))
            ->where('goals', '>', 0)
            ->selectRaw('game_player_id, SUM(goals) as goals')
            ->groupBy('game_player_id')
            ->orderByDesc('goals')
            ->limit(self::TOP_SCORERS)
            ->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $players = GamePlayer::whereIn('id', $rows->pluck('game_player_id'))->get()->keyBy('id');
        $teamOf = GameContract::where('game_save_id', $gameSave->id)->whereIn('game_player_id', $rows->pluck('game_player_id'))
            ->pluck('game_team_id', 'game_player_id');

        return $rows->map(function ($row) use ($players, $teamOf, $teams, $flagUrls) {
            $player = $players[$row->game_player_id] ?? null;
            $team = $teams[$teamOf[$row->game_player_id] ?? 0] ?? null;

            return [
                'name' => $player ? trim($player->firstname.' '.$player->lastname) : '?',
                'team' => $team->name ?? '?',
                'flag' => Nationality::flag($team->name ?? ''),
                'flag_url' => $flagUrls[$team->name ?? ''] ?? null,
                'goals' => (int) $row->goals,
            ];
        })->all();
    }

    /**
     * Chiffres clés : matchs, buts, moyenne par match, plus large victoire.
     *
     * @return array{matches:int, goals:int, average:float, teams:int, biggest_win: array<string, mixed>|null}
     */
    private function figures($matches, $teams): array
    {
        $goals = (int) $matches->sum(fn (GameMatch $m) => (int) $m->home_score + (int) $m->away_score);
        $biggest = $matches->sortByDesc(fn (GameMatch $m) => abs((int) $m->home_score - (int) $m->away_score))->first();

        return [
            'matches' => $matches->count(),
            'goals' => $goals,
            'average' => $matches->isEmpty() ? 0.0 : round($goals / $matches->count(), 1),
            'teams' => $teams->count(),
            'biggest_win' => $biggest ? [
                'home' => $teams[$biggest->home_team_id]->name ?? null,
                'away' => $teams[$biggest->away_team_id]->name ?? null,
                'home_score' => (int) $biggest->home_score,
                'away_score' => (int) $biggest->away_score,
            ] : null,
        ];
    }
}

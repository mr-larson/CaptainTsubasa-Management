<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameMatchEventLog;
use App\Models\GameSaves\GameMatchPlayerStat;
use App\Services\PlayerStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Détail des matchs hors de game_matches.match_stats : bilans joueurs dans
 * game_match_player_stats, déroulé dans game_match_events (1-1).
 */
class MatchStatsSplitTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function playerStats(int $goals, int $duelsWon = 0): array
    {
        return [
            'offense'   => ['shot' => ['attempts' => 3, 'success' => $goals], 'goals' => $goals],
            'defense'   => ['gkSpecial' => ['attempts' => 2, 'success' => 1]],
            'duelsWon'  => $duelsWon,
            'duelsLost' => 1,
        ];
    }

    public function test_players_and_events_are_stored_outside_the_column(): void
    {
        $save = $this->makeSave();
        [$home, $players] = $this->makeTeamWithSquad($save);
        $away   = $this->makeTeam($save);
        $scorer = $players->first();

        $match = $this->makeMatch($save, $home, $away, [
            'status' => 'played', 'home_score' => 2, 'away_score' => 0,
            'match_stats' => [
                'teams'   => ['home' => ['goals' => 2], 'away' => ['goals' => 0]],
                'players' => [(string) $scorer->id => $this->playerStats(2, 4)],
                'events'  => [['turn' => 1], ['turn' => 2]],
            ],
        ]);

        $raw = json_decode(DB::table('game_matches')->where('id', $match->id)->value('match_stats'), true);
        $this->assertSame(['teams'], array_keys($raw));

        $row = GameMatchPlayerStat::where('game_match_id', $match->id)->sole();
        $this->assertSame([$save->id, $scorer->id, 2, 3, 1, 4], [
            (int) $row->game_save_id, (int) $row->game_player_id, (int) $row->goals,
            (int) $row->shot_attempts, (int) $row->gk_special_success, (int) $row->duels_won,
        ]);
        $this->assertCount(2, GameMatchEventLog::where('game_match_id', $match->id)->sole()->events);

        // Recomposition pour la feuille de match.
        $full = $match->fresh()->fullStats();
        $this->assertSame(2, $full['players'][$scorer->id]['offense']['goals']);
        $this->assertSame(2, $full['events'][1]['turn']);
        $this->assertSame(2, $full['teams']['home']['goals']);
    }

    public function test_updating_the_summary_keeps_players_and_events(): void
    {
        $save = $this->makeSave();
        [$home, $players] = $this->makeTeamWithSquad($save);
        $away  = $this->makeTeam($save);
        $match = $this->makeMatch($save, $home, $away, [
            'status' => 'played',
            'match_stats' => [
                'players' => [(string) $players->first()->id => $this->playerStats(1)],
                'events'  => [['turn' => 1]],
            ],
        ]);

        // Écriture « résumé seul », comme la progression ou l'alchimie.
        $match = $match->fresh();
        $stats = $match->match_stats;
        $stats['progression'] = [['player_id' => 1]];
        $match->match_stats = $stats;
        $match->save();

        $this->assertCount(1, $match->fresh()->playerStatsMap());
        $this->assertCount(1, $match->fresh()->eventList());

        // Une clé `players` présente remplace les lignes.
        $match->update(['match_stats' => ['players' => []]]);
        $this->assertSame([], $match->fresh()->playerStatsMap());
        $this->assertCount(1, $match->fresh()->eventList());
    }

    public function test_details_are_deleted_with_the_match(): void
    {
        $save = $this->makeSave();
        [$home, $players] = $this->makeTeamWithSquad($save);
        $away  = $this->makeTeam($save);
        $match = $this->makeMatch($save, $home, $away, [
            'status' => 'played',
            'match_stats' => ['players' => [(string) $players->first()->id => []], 'events' => [['turn' => 1]]],
        ]);

        GameMatch::where('game_save_id', $save->id)->delete();

        $this->assertSame(0, GameMatchPlayerStat::where('game_match_id', $match->id)->count());
        $this->assertSame(0, GameMatchEventLog::where('game_match_id', $match->id)->count());
    }

    public function test_season_aggregate_sums_played_matches_only(): void
    {
        $save = $this->makeSave();
        [$home, $players] = $this->makeTeamWithSquad($save);
        $away   = $this->makeTeam($save);
        $scorer = $players->first();
        $key    = (string) $scorer->id;

        foreach ([1 => 2, 2 => 1] as $week => $goals) {
            $this->makeMatch($save, $home, $away, [
                'week' => $week, 'status' => 'played',
                'match_stats' => ['players' => [$key => $this->playerStats($goals, 2)]],
            ]);
        }
        $this->makeMatch($save, $home, $away, [
            'week' => 3, 'status' => 'scheduled',
            'match_stats' => ['players' => [$key => $this->playerStats(5)]],
        ]);

        $stats = PlayerStatsService::aggregateForSave($save);

        $this->assertSame([$key], array_map('strval', array_keys($stats)));
        $this->assertSame(3, $stats[$key]['offense']['goals']);
        $this->assertSame(['attempts' => 6, 'success' => 3], $stats[$key]['offense']['shot']);
        $this->assertSame(['attempts' => 4, 'success' => 2], $stats[$key]['defense']['gkSpecial']);
        $this->assertSame([4, 2], [$stats[$key]['duelsWon'], $stats[$key]['duelsLost']]);
        $this->assertSame(array_keys(PlayerStatsService::emptyStats()['offense']), array_keys($stats[$key]['offense']));
    }
}

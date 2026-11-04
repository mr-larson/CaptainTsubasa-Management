<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameSanction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Classements d'une journée (onglet Calendrier).
 */
class WeekLeadersTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function playerStats(array $o = []): array
    {
        return [
            'offense' => [
                'goals'   => $o['goals'] ?? 0,
                'pass'    => ['attempts' => 10, 'success' => $o['passes'] ?? 0],
                'dribble' => ['attempts' => 5, 'success' => $o['dribbles'] ?? 0],
                'shot'    => ['attempts' => 3, 'success' => $o['goals'] ?? 0],
            ],
            'defense' => [
                'hands'  => ['attempts' => 5, 'success' => $o['saves'] ?? 0],
                'tackle' => ['attempts' => 3, 'success' => $o['tackles'] ?? 0],
            ],
            'duelsWon'  => $o['won'] ?? 0,
            'duelsLost' => $o['lost'] ?? 0,
        ];
    }

    public function test_week_leaders_rank_players_of_the_week_only(): void
    {
        $save = $this->makeSave();
        [$a, $aPlayers] = $this->makeTeamWithSquad($save);
        [$b, $bPlayers] = $this->makeTeamWithSquad($save);

        $match = $this->makeMatch($save, $a, $b, [
            'week' => 3, 'status' => 'played', 'home_score' => 2, 'away_score' => 1,
            'match_stats' => [
                'starters' => ['home' => $aPlayers->pluck('id')->all(), 'away' => $bPlayers->pluck('id')->all()],
                'players'  => [
                    $aPlayers[9]->id => $this->playerStats(['goals' => 2, 'won' => 6, 'lost' => 2]),
                    $bPlayers[9]->id => $this->playerStats(['goals' => 1, 'passes' => 7]),
                    $bPlayers[0]->id => $this->playerStats(['saves' => 4]),
                ],
            ],
        ]);
        GameSanction::create(['game_save_id' => $save->id, 'game_player_id' => $aPlayers[4]->id, 'game_match_id' => $match->id,
            'type' => 'yellow', 'weeks_suspended' => 0, 'week_match' => 3, 'week_return' => 4, 'yellow_card_count' => 1]);
        GameSanction::create(['game_save_id' => $save->id, 'game_player_id' => $bPlayers[3]->id, 'game_match_id' => $match->id,
            'type' => 'red', 'weeks_suspended' => 1, 'week_match' => 3, 'week_return' => 5, 'yellow_card_count' => 0]);
        GameInjury::create(['game_save_id' => $save->id, 'game_player_id' => $aPlayers[6]->id, 'game_match_id' => $match->id,
            'severity' => 'light', 'weeks_out' => 2, 'week_injured' => 3, 'week_return' => 5, 'description' => 'Entorse']);

        // Autre semaine : ignorée
        $this->makeMatch($save, $b, $a, [
            'week' => 4, 'status' => 'played', 'home_score' => 5, 'away_score' => 0,
            'match_stats' => ['players' => [$bPlayers[8]->id => $this->playerStats(['goals' => 5])]],
        ]);

        $res = $this->actingAs(User::find($save->user_id))
            ->getJson(route('game-saves.weeks.leaders', [$save, 3]))
            ->assertOk()
            ->assertJsonPath('matches', 1)
            ->json('categories');

        $this->assertSame($aPlayers[9]->id, $res['scorers'][0]['player_id']);
        $this->assertSame(2, $res['scorers'][0]['value']);
        $this->assertSame($a->id, $res['scorers'][0]['team_id']);
        $this->assertSame($b->id, $res['scorers'][1]['team_id']);
        $this->assertCount(2, $res['scorers']);                 // le gardien sans but n'y figure pas
        $this->assertSame($bPlayers[0]->id, $res['keepers'][0]['player_id']);
        $this->assertSame($bPlayers[9]->id, $res['passers'][0]['player_id']);
        $this->assertSame('75 %', $res['duels'][0]['detail']);
        $this->assertCount(1, $res['motm']);                    // un homme du match par rencontre
        $this->assertSame($aPlayers[9]->id, $res['motm'][0]['player_id']);

        // Fautes : rouge, puis jaune, puis blessure ; équipe du joueur résolue
        $this->assertSame(['🟥', '🟨', '🤕'], array_column($res['fouls'], 'value'));
        $this->assertSame($b->id, $res['fouls'][0]['team_id']);
        $this->assertStringContainsString('suspendu 1 sem.', $res['fouls'][0]['detail']);
        $this->assertStringContainsString('Entorse', $res['fouls'][2]['detail']);
    }

    public function test_week_leaders_are_scoped_to_the_owner(): void
    {
        $save = $this->makeSave();

        $this->actingAs(User::factory()->create())
            ->getJson(route('game-saves.weeks.leaders', [$save, 1]))
            ->assertForbidden();
    }
}

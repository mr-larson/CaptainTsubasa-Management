<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameInjury;
use App\Models\GameSaves\GameSanction;
use App\Models\User;
use App\Services\Matches\MatchLineupSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Données de la feuille de match : instantané (capitaine, poste occupé) et
 * incidents (cartons, blessures) renvoyés avec le détail du match.
 */
class MatchSheetDataTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_snapshot_records_captain_and_lineup_slot(): void
    {
        $save = $this->makeSave();
        [$home, $homePlayers] = $this->makeTeamWithSquad($save);
        [$away]               = $this->makeTeamWithSquad($save);

        $homePlayers[4]->contracts()->update(['is_captain' => true]);
        $save->update(['state' => ['lineup' => [$home->id => ['slots' => [1 => $homePlayers[0]->id, 7 => $homePlayers[4]->id]]]]]);

        $match    = $this->makeMatch($save, $home, $away);
        $snapshot = app(MatchLineupSnapshot::class)->build($match->fresh());

        $byId = collect($snapshot['home']['players'])->keyBy('id');
        $this->assertTrue($byId[$homePlayers[4]->id]['is_captain']);
        $this->assertSame(7, $byId[$homePlayers[4]->id]['slot']);
        $this->assertSame(1, $byId[$homePlayers[0]->id]['slot']);
        $this->assertNull($byId[$homePlayers[2]->id]['slot']);
        $this->assertFalse($byId[$homePlayers[2]->id]['is_captain']);
    }

    public function test_match_stats_endpoint_returns_cards_and_injuries_of_the_match(): void
    {
        $save = $this->makeSave();
        [$home, $homePlayers] = $this->makeTeamWithSquad($save);
        [$away, $awayPlayers] = $this->makeTeamWithSquad($save);
        $match = $this->makeMatch($save, $home, $away, ['status' => 'played', 'home_score' => 1, 'away_score' => 0, 'match_stats' => ['events' => []]]);
        $other = $this->makeMatch($save, $away, $home, ['week' => 2]);

        GameSanction::create(['game_save_id' => $save->id, 'game_player_id' => $homePlayers[3]->id, 'game_match_id' => $match->id,
            'type' => 'yellow', 'weeks_suspended' => 0, 'week_match' => 1, 'week_return' => 2, 'yellow_card_count' => 1]);
        GameSanction::create(['game_save_id' => $save->id, 'game_player_id' => $awayPlayers[3]->id, 'game_match_id' => $other->id,
            'type' => 'red', 'weeks_suspended' => 1, 'week_match' => 2, 'week_return' => 4, 'yellow_card_count' => 0]);
        GameInjury::create(['game_save_id' => $save->id, 'game_player_id' => $awayPlayers[5]->id, 'game_match_id' => $match->id,
            'severity' => 'light', 'weeks_out' => 2, 'week_injured' => 1, 'week_return' => 3, 'description' => 'Entorse']);

        $this->actingAs(User::find($save->user_id))
            ->getJson(route('game-saves.matches.stats', [$save, $match]))
            ->assertOk()
            ->assertJsonCount(1, 'incidents.sanctions')
            ->assertJsonPath('incidents.sanctions.0.player_id', $homePlayers[3]->id)
            ->assertJsonPath('incidents.sanctions.0.type', 'yellow')
            ->assertJsonCount(1, 'incidents.injuries')
            ->assertJsonPath('incidents.injuries.0.description', 'Entorse');
    }
}

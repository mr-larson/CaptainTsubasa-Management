<?php

namespace Tests\Feature;

use App\Services\PostMatchProgressionService;
use App\Services\SeasonService;
use App\Services\TournamentService;
use App\Services\TrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Test de CONTRAT sur le blob game_saves.state (JSON).
 *
 * Le state est le point de couplage de tout le jeu : de nombreux services
 * lisent et écrivent des clés sans schéma ni typage. Ce test fige la forme des
 * clés critiques produites par chaque service, pour qu'une évolution qui
 * casserait silencieusement la structure soit détectée immédiatement.
 *
 * @see SeasonService, TournamentService, TrainingService, PostMatchProgressionService
 */
class StateContractTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_training_state_shape(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['is_controlled' => true]);
        $save->controlled_game_team_id = $team->id;
        $save->save();

        $player = $this->makePlayer($save, ['stamina' => 100]);
        $this->makeContract($save, $team, $player);

        app(TrainingService::class)->applyTrainings($save, 1, 1, [
            ['player_id' => $player->id, 'stat' => 'shot'],
        ]);

        $training = $save->fresh()->state['training'];
        // MySQL ne préserve pas l'ordre des clés JSON → on vérifie la présence.
        $this->assertEqualsCanonicalizing(['season', 'week', 'entries'], array_keys($training));
        $this->assertSame(1, $training['season']);
        $this->assertSame(1, $training['week']);

        $entry = $training['entries'][0];
        $this->assertArrayHasKey('player_id', $entry);
        $this->assertArrayHasKey('stat', $entry);
        $this->assertArrayHasKey('gain', $entry);
        $this->assertArrayHasKey('stamina_cost', $entry);
    }

    public function test_season_progression_state_shape(): void
    {
        $save   = $this->makeSave();
        [$team] = $this->makeTeamWithSquad($save);
        $scorer = $this->makePlayer($save, ['position' => 'ATT', 'shot' => 50]);
        $this->makeContract($save, $team, $scorer);

        $match = $this->makeMatch($save, $team, $team, [
            'status'      => 'played',
            'home_score'  => 2,
            'away_score'  => 0,
            'match_stats' => ['players' => [(string) $scorer->id => ['offense' => ['goals' => 2], 'defense' => [], 'duelsWon' => 0]]],
        ]);

        app(PostMatchProgressionService::class)->applyForMatch($save, $match);

        $state = $save->fresh()->state;

        // season_progression : [season => [playerId => cumul int]]
        $this->assertArrayHasKey(1, $state['season_progression']);
        $this->assertIsInt($state['season_progression'][1][$scorer->id]);

        // match_progression_history : liste d'entrées datées par match.
        $history = $state['match_progression_history'];
        $this->assertNotEmpty($history);
        $this->assertEqualsCanonicalizing(['match_id', 'week', 'season', 'count'], array_keys($history[0]));
    }

    public function test_season_history_recap_shape(): void
    {
        $save = $this->makeSave();
        $this->makeTeam($save, ['name' => 'A', 'wins' => 3]);
        $this->makeTeam($save, ['name' => 'B', 'wins' => 1]);

        app(SeasonService::class)->endSeason($save);

        $state = $save->fresh()->state;

        $this->assertArrayHasKey('season_history', $state);
        $this->assertArrayHasKey('last_season_recap', $state);

        $recap = $state['last_season_recap'];
        foreach (['season', 'champion', 'mvp', 'standings', 'transfer_requests', 'career_verdict'] as $key) {
            $this->assertArrayHasKey($key, $recap, "Le récap de saison doit contenir « {$key} ».");
        }

        // Une ligne de classement porte rang, équipe, points et prime.
        $row = $recap['standings'][0];
        foreach (['rank', 'team_id', 'name', 'points', 'goal_diff', 'prize'] as $key) {
            $this->assertArrayHasKey($key, $row);
        }
    }

    public function test_tournament_state_shape(): void
    {
        $save = $this->makeSave(['competition_type' => 'world_cup']);
        foreach (range(1, 4) as $i) {
            $this->makeTeam($save);
        }

        app(TournamentService::class)->generateGroups($save);

        $t = $save->fresh()->state['tournament'];
        foreach (['groups', 'stage', 'group_last_week', 'champion_team_id'] as $key) {
            $this->assertArrayHasKey($key, $t);
        }
        $this->assertSame('group', $t['stage']);
        $this->assertNull($t['champion_team_id']);
    }
}

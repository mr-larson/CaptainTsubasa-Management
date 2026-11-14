<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Models\User;
use App\Services\Matches\MatchSimulationService;
use App\Services\Season\WeekClosingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * « Simuler mon match » : gardes de la demande, interdictions et enchaînement
 * après la simulation (service puis route).
 */
class MatchSimulationServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function service(): MatchSimulationService
    {
        return app(MatchSimulationService::class);
    }

    /**
     * Quatre équipes ; $humans d'entre elles sont dirigées par des humains (sièges 1, 2…).
     * Deux matchs en semaine 1 : équipe 0 contre équipe 2, équipe 1 contre équipe 3.
     */
    private function world(int $humans = 1): array
    {
        $save  = $this->makeSave();
        $teams = [];
        foreach (range(1, 4) as $i) {
            [$team] = $this->makeTeamWithSquad($save, $i <= $humans ? ['is_controlled' => true, 'human_seat' => $i] : []);
            $teams[] = $team;
        }
        $save->update(['controlled_game_team_id' => $teams[0]->id]);
        $matches = [$this->makeMatch($save, $teams[0], $teams[2]), $this->makeMatch($save, $teams[1], $teams[3])];

        return [$save->fresh(), $teams, $matches];
    }

    public function test_an_already_played_match_is_ignored(): void
    {
        [$save, , $matches] = $this->world();
        $matches[0]->update(['status' => 'played', 'home_score' => 1, 'away_score' => 0]);

        $result = $this->service()->simulateNextMatch($save, $matches[0]);

        $this->assertFalse($result->isForbidden());
        $this->assertSame(WeekClosingService::OUTCOME_PLAY, $result->outcome);
        $this->assertNull($result->summary);
        $this->assertSame(1, (int) $save->fresh()->week);
    }

    public function test_a_finished_career_goes_to_the_game_over_screen(): void
    {
        [$save, , $matches] = $this->world();
        $save->update(['state' => ['career' => ['status' => 'fired']]]);

        $result = $this->service()->simulateNextMatch($save->fresh(), $matches[0]);

        $this->assertSame(WeekClosingService::OUTCOME_GAME_OVER, $result->outcome);
        $this->assertSame(MatchStatus::Scheduled, $matches[0]->fresh()->status);
    }

    public function test_a_match_of_another_team_is_forbidden(): void
    {
        [$save, , $matches] = $this->world();

        $result = $this->service()->simulateNextMatch($save, $matches[1]); // match des équipes IA

        $this->assertTrue($result->isForbidden());
        $this->assertSame(MatchStatus::Scheduled, $matches[1]->fresh()->status);
    }

    public function test_a_derby_between_two_humans_is_forbidden(): void
    {
        $save = $this->makeSave();
        [$a] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        [$b] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 2]);
        $this->makeTeamWithSquad($save);
        $this->makeTeamWithSquad($save);
        $save->update(['controlled_game_team_id' => $a->id]);
        $match = $this->makeMatch($save, $a, $b);

        $result = $this->service()->simulateNextMatch($save->fresh(), $match);

        $this->assertTrue($result->isForbidden());
        $this->assertSame('Ce match oppose deux managers humains.', $result->forbiddenMessage);
    }

    public function test_the_match_is_simulated_and_the_week_closes_when_no_other_human_must_play(): void
    {
        [$save, $teams, $matches] = $this->world(1);

        $result = $this->service()->simulateNextMatch($save, $matches[0]);

        $this->assertFalse($result->isForbidden());
        $this->assertSame(MatchStatus::Played, $matches[0]->fresh()->status);
        $this->assertSame(MatchStatus::Played, $matches[1]->fresh()->status, 'le match IA de la semaine est simulé à la clôture');
        $this->assertSame(2, (int) $save->fresh()->week);
        $this->assertStringContainsString($teams[0]->name, $result->summary);
        $this->assertMatchesRegularExpression('/\d–\d/', $result->summary);
    }

    public function test_the_hand_passes_to_the_other_human_instead_of_closing_the_week(): void
    {
        [$save, $teams, $matches] = $this->world(2); // le siège 2 a encore son match à jouer

        $result = $this->service()->simulateNextMatch($save, $matches[0]);

        $this->assertSame(WeekClosingService::OUTCOME_PLAY, $result->outcome);
        $this->assertSame($teams[1]->id, (int) $save->fresh()->controlled_game_team_id);
        $this->assertSame(1, (int) $save->fresh()->week);
        $this->assertSame(MatchStatus::Scheduled, $matches[1]->fresh()->status);
    }

    public function test_the_route_answers_403_for_a_forbidden_match_and_redirects_otherwise(): void
    {
        [$save, , $matches] = $this->world();
        $owner = User::find($save->user_id);

        $this->actingAs($owner)->post(route('game-saves.matches.simulate', [$save, $matches[1]]))->assertForbidden();

        $this->actingAs($owner)->post(route('game-saves.matches.simulate', [$save, $matches[0]]))
            ->assertRedirect()
            ->assertSessionHas('success');
    }
}

<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameMatch;
use App\Services\WeekAdvanceService;
use App\Services\WeekClosingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Avancer le calendrier : garde-fous de la simulation de semaine et enchaînement
 * après un match (hot-seat, clôture de semaine).
 */
class WeekAdvanceServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function service(): WeekAdvanceService
    {
        return app(WeekAdvanceService::class);
    }

    /**
     * Une partie de 4 équipes dont $humans managers humains, un match prévu par paire
     * en semaine 1. Le manager actif est le siège 1.
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

    public function test_nothing_happens_outside_a_match_phase(): void
    {
        [$save] = $this->world();
        $save->update(['phase' => 'season_end']);

        $this->assertSame(WeekClosingService::OUTCOME_PLAY, $this->service()->simulateWeek($save->fresh(), null, false));
        $this->assertSame(1, (int) $save->fresh()->week);
    }

    public function test_a_finished_career_goes_to_the_game_over_screen(): void
    {
        [$save] = $this->world();
        $save->update(['state' => ['career' => ['status' => 'fired']]]);

        $this->assertSame(WeekClosingService::OUTCOME_GAME_OVER, $this->service()->simulateWeek($save->fresh(), null, false));
    }

    public function test_a_replayed_request_for_a_past_week_is_ignored(): void
    {
        [$save] = $this->world();
        $save->update(['week' => 3]);

        $outcome = $this->service()->simulateWeek($save->fresh(), 2, false); // le navigateur croit être en semaine 2

        $this->assertSame(WeekClosingService::OUTCOME_PLAY, $outcome);
        $this->assertSame(3, (int) $save->fresh()->week);
    }

    public function test_a_human_with_a_pending_match_gets_the_hand_instead_of_a_simulation(): void
    {
        [$save, $teams] = $this->world(2); // sièges 1 et 2 ; aucun match joué

        $outcome = $this->service()->simulateWeek($save, 1, false);

        $this->assertSame(WeekClosingService::OUTCOME_PLAY, $outcome);
        $this->assertSame($teams[0]->id, (int) $save->fresh()->controlled_game_team_id); // le siège 1 a encore à jouer
        $this->assertSame(1, (int) $save->fresh()->week);
        $this->assertSame(0, GameMatch::where('game_save_id', $save->id)->where('status', 'played')->count());
    }

    public function test_the_hand_passes_to_the_next_human_after_a_match(): void
    {
        [$save, $teams, $matches] = $this->world(2);
        $matches[0]->update(['status' => 'played', 'home_score' => 1, 'away_score' => 0]); // le siège 1 a joué

        $outcome = $this->service()->advanceAfterMatch($save, 1);

        $this->assertSame(WeekClosingService::OUTCOME_PLAY, $outcome);
        $this->assertSame($teams[1]->id, (int) $save->fresh()->controlled_game_team_id);
        $this->assertSame(1, (int) $save->fresh()->week);
    }

    public function test_the_week_is_simulated_and_closed_once_every_human_has_played(): void
    {
        [$save, , $matches] = $this->world(1);
        $matches[0]->update(['status' => 'played', 'home_score' => 2, 'away_score' => 1]); // le seul humain a joué

        $outcome = $this->service()->simulateWeek($save->fresh(), 1, false);

        $this->assertSame(WeekClosingService::OUTCOME_PLAY, $outcome);
        $this->assertSame('played', $matches[1]->fresh()->status->value); // le match IA est simulé
        $this->assertSame(2, (int) $save->fresh()->week);                 // la semaine avance
    }
}

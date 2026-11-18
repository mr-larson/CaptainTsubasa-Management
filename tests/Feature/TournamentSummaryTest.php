<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GameSave;
use App\Services\WorldCup\TournamentService;
use App\Services\WorldCup\TournamentSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/** Bilan de fin de tournoi : podium, parcours du manager, route de l'écran final. */
class TournamentSummaryTest extends TestCase
{
    use BuildsGameWorld;
    use RefreshDatabase;

    private function play(GameSave $save, array $rounds): void
    {
        GameMatch::where('game_save_id', $save->id)->whereIn('round', $rounds)->where('status', 'scheduled')
            ->get()->each(fn (GameMatch $m) => $m->update(['status' => 'played', 'home_score' => 2, 'away_score' => 0]));
    }

    /** Tournoi à 8 équipes dont la première est humaine, avancé jusqu'à $untilStage. */
    private function tournament(bool $finished): GameSave
    {
        $save = $this->makeSave(['competition_type' => 'world_cup']);
        $human = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1, 'name' => 'Japon']);
        foreach (range(2, 8) as $i) {
            $this->makeTeam($save);
        }
        $save->controlled_game_team_id = $human->id;
        $save->save();

        $service = app(TournamentService::class);
        $service->generateGroups($save);
        $steps = [['group_a', 'group_b'], ['semi'], ['final', 'third']];
        foreach ($finished ? $steps : [$steps[0]] as $rounds) {
            $this->play($save, $rounds);
            $service->advance($save);
        }

        return $save->refresh();
    }

    public function test_summary_is_null_while_the_tournament_is_running(): void
    {
        $this->assertNull(app(TournamentSummaryService::class)->summary($this->tournament(false)));
    }

    public function test_summary_lists_the_podium_the_manager_journey_and_key_figures(): void
    {
        $save = $this->tournament(true);

        $summary = app(TournamentSummaryService::class)->summary($save);

        $this->assertSame([1, 2, 3, 4], array_column($summary['podium'], 'rank'));
        $this->assertCount(4, array_unique(array_column($summary['podium'], 'team_id')));
        $this->assertSame($summary['champion']['team_id'], $summary['podium'][0]['team_id']);

        $manager = $summary['managers'][0];
        $this->assertSame('Japon', $manager['name']);
        $this->assertSame($manager['record']['played'], count($manager['games']));
        $this->assertNotSame('Tournoi terminé', $manager['result']);

        $this->assertSame(GameMatch::where('game_save_id', $save->id)->where('status', 'played')->count(), $summary['figures']['matches']);
        $this->assertSame(2.0, $summary['figures']['average']);
    }

    public function test_the_end_screen_is_only_reachable_once_the_tournament_is_over(): void
    {
        $running = $this->tournament(false);
        $this->actingAs($running->user)->get(route('game-saves.tournament-end', $running))
            ->assertRedirect(route('game-saves.Play', $running));

        $done = $this->tournament(true);
        $this->actingAs($done->user)->get(route('game-saves.tournament-end', $done))
            ->assertInertia(fn ($page) => $page->component('GameSaves/TournamentEnd')
                ->has('summary.podium', 4)->has('summary.managers', 1)->has('summary.figures'));
    }
}

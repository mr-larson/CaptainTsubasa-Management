<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Écran de fin de mandat : un licenciement en cours de saison affiche le bilan
 * de la saison interrompue (rang, bilan, buts, dernière semaine jouée).
 */
class GameOverSummaryTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_mid_season_dismissal_shows_the_interrupted_season(): void
    {
        $save = $this->makeSave();
        $mine = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1, 'name' => 'Nankatsu']);
        $a    = $this->makeTeam($save, ['name' => 'Toho']);
        $b    = $this->makeTeam($save, ['name' => 'Hanawa']);

        $this->makeMatch($save, $mine, $a, ['week' => 1, 'status' => 'played', 'home_score' => 0, 'away_score' => 2]);
        $this->makeMatch($save, $b, $mine, ['week' => 2, 'status' => 'played', 'home_score' => 1, 'away_score' => 1]);
        $this->makeMatch($save, $a, $b, ['week' => 2, 'status' => 'played', 'home_score' => 3, 'away_score' => 0]);

        $state = $save->state ?? [];
        $state['career'] = [
            'status' => 'fired', 'fired_reason' => 'mid_season', 'confidence' => 0,
            'difficulty' => 'standard', 'titles_won' => 0, 'titles_required' => 2, 'history' => [],
            'mandate' => ['season' => 1, 'target_rank' => 1, 'label' => 'Être champion'],
        ];
        $save->update(['controlled_game_team_id' => $mine->id, 'state' => $state]);

        $page = $this->actingAs(User::find($save->user_id))
            ->get(route('game-saves.game-over', $save))
            ->assertOk()
            ->viewData('page')['props'];

        $summary = $page['finalSeason'];
        $this->assertSame('Nankatsu', $summary['team']);
        $this->assertSame(2, $summary['last_week']);
        $this->assertSame([0, 1, 1], [$summary['wins'], $summary['draws'], $summary['losses']]);
        $this->assertSame([1, 3], [$summary['goals_for'], $summary['goals_against']]);
        $this->assertSame(1, $summary['points']);
        // Toho 6 pts ; Nankatsu et Hanawa 1 pt, départagés à la différence de buts (-2 contre -3).
        $this->assertSame(2, $summary['rank']);
        $this->assertSame('Être champion', $summary['objective']);
    }
}

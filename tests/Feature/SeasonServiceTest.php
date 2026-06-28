<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GameMatch;
use App\Services\SeasonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Cycle de saison (SeasonService) : longueur, clôture (classement, primes,
 * récap dans state) et redémarrage (reset, expiration des contrats, draft).
 * C'est le squelette temporel du mode Ligue.
 */
class SeasonServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private SeasonService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SeasonService::class);
    }

    public function test_season_length_is_derived_from_team_count(): void
    {
        $save = $this->makeSave();

        // 4 équipes (pair) → (n-1) * 2 aller-retour partiel = 6 journées.
        foreach (range(1, 4) as $i) {
            $this->makeTeam($save);
        }

        $this->assertSame(6, $this->service->getSeasonLength($save));
    }

    public function test_season_length_falls_back_when_too_few_teams(): void
    {
        $save = $this->makeSave();
        $this->makeTeam($save); // une seule équipe

        $this->assertSame(28, $this->service->getSeasonLength($save));
    }

    public function test_is_season_over_compares_week_to_length(): void
    {
        $save = $this->makeSave();
        foreach (range(1, 4) as $i) {
            $this->makeTeam($save);
        }

        $save->week = 6;
        $this->assertFalse($this->service->isSeasonOver($save), 'La dernière journée est encore à jouer.');

        $save->week = 7;
        $this->assertTrue($this->service->isSeasonOver($save), 'Au-delà de la longueur, la saison est finie.');
    }

    public function test_end_season_ranks_teams_and_awards_prizes(): void
    {
        $save = $this->makeSave();

        $first  = $this->makeTeam($save, ['name' => 'Champion', 'wins' => 5, 'draws' => 0, 'losses' => 1, 'goals_for' => 12, 'goals_against' => 4, 'budget' => 1000]);
        $second = $this->makeTeam($save, ['name' => 'Dauphin',  'wins' => 3, 'draws' => 1, 'losses' => 2, 'goals_for' => 8,  'goals_against' => 7, 'budget' => 1000]);
        $last   = $this->makeTeam($save, ['name' => 'Lanterne', 'wins' => 0, 'draws' => 1, 'losses' => 5, 'goals_for' => 2,  'goals_against' => 14, 'budget' => 1000]);

        $recap = $this->service->endSeason($save);

        // Classement par points → champion en tête.
        $this->assertSame($first->id, $recap['champion']['id']);
        $this->assertSame($first->id, $recap['standings'][0]['team_id']);
        $this->assertSame($last->id, $recap['standings'][2]['team_id']);

        // Primes décroissantes : 6000 / 5600 / 5200.
        $this->assertSame(6000, $recap['standings'][0]['prize']);
        $this->assertSame(5600, $recap['standings'][1]['prize']);
        $this->assertSame(5200, $recap['standings'][2]['prize']);

        // La prime est créditée au budget.
        $this->assertSame(1000 + 6000, (int) $first->fresh()->budget);

        // Récap persisté + phase de fin de saison.
        $save->refresh();
        $this->assertSame('season_end', $save->phase);
        $this->assertArrayHasKey(1, $save->state['season_history']);
        $this->assertSame($first->id, $save->state['last_season_recap']['champion']['id']);
    }

    public function test_end_season_tiebreaks_on_goal_difference(): void
    {
        $save = $this->makeSave();

        // Mêmes points (9), départage par différence de buts.
        $worseDiff = $this->makeTeam($save, ['name' => 'B', 'wins' => 3, 'draws' => 0, 'losses' => 0, 'goals_for' => 5, 'goals_against' => 4]);
        $betterDiff = $this->makeTeam($save, ['name' => 'A', 'wins' => 3, 'draws' => 0, 'losses' => 0, 'goals_for' => 9, 'goals_against' => 1]);

        $recap = $this->service->endSeason($save);

        $this->assertSame($betterDiff->id, $recap['standings'][0]['team_id'], 'Meilleure diff de buts → 1er.');
        $this->assertSame($worseDiff->id, $recap['standings'][1]['team_id']);
    }

    public function test_start_new_season_resets_state_and_launches_draft(): void
    {
        $save = $this->makeSave(['season' => 1]);

        $strong = $this->makeTeam($save, ['name' => 'Fort',  'wins' => 6, 'goals_for' => 15, 'goals_against' => 3]);
        $weak   = $this->makeTeam($save, ['name' => 'Faible', 'wins' => 0, 'losses' => 6, 'goals_for' => 2, 'goals_against' => 15]);

        // Un contrat et un match existants doivent être purgés.
        $player = $this->makePlayer($save, ['number' => 9]);
        $this->makeContract($save, $strong, $player);
        $this->makeMatch($save, $strong, $weak, ['status' => 'played', 'home_score' => 3, 'away_score' => 0]);

        $this->service->startNewSeason($save);
        $save->refresh();

        // Avancement de saison + reset.
        $this->assertSame(2, (int) $save->season);
        $this->assertSame(1, (int) $save->week);
        $this->assertSame('intersaison_draft', $save->phase);
        $this->assertSame(0, (int) $strong->fresh()->wins);
        $this->assertSame(0, (int) $weak->fresh()->goals_against);

        // Contrats et calendrier purgés.
        $this->assertSame(0, GameContract::where('game_save_id', $save->id)->count());
        $this->assertSame(0, GameMatch::where('game_save_id', $save->id)->count());
        $this->assertNull($player->fresh()->number);

        // Draft initialisée, ordre = inverse du classement (le plus faible pioche en premier).
        $this->assertArrayHasKey('draft', $save->state);
        $this->assertSame([$weak->id, $strong->id], $save->state['draft']['order']);
    }
}

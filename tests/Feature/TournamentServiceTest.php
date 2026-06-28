<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameMatch;
use App\Services\TournamentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Moteur de tournoi (TournamentService) du mode Coupe du Monde. On vérifie la
 * structure du bracket et la progression des phases (poules → demies → finale
 * → champion), pilotées par state['tournament']. Très stateful, donc fort
 * gain de filet de sécurité.
 */
class TournamentServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private TournamentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TournamentService::class);
    }

    /** Joue (score domicile gagnant) tous les matchs programmés de ces tours. */
    private function playRounds(int $saveId, array $rounds): void
    {
        GameMatch::where('game_save_id', $saveId)
            ->whereIn('round', $rounds)
            ->where('status', 'scheduled')
            ->get()
            ->each(function (GameMatch $m) {
                $m->update(['status' => 'played', 'home_score' => 2, 'away_score' => 0]);
            });
    }

    public function test_generate_groups_creates_two_groups_and_fixtures(): void
    {
        $save = $this->makeSave(['competition_type' => 'world_cup']);
        foreach (range(1, 4) as $i) {
            $this->makeTeam($save);
        }

        $this->service->generateGroups($save);
        $save->refresh();

        $t = $save->state['tournament'];
        $this->assertSame('group', $t['stage']);
        $this->assertSame('group', $save->phase);
        $this->assertCount(2, $t['groups']); // 4 sélections → 2 poules

        // Une poule de 2 → 1 match par poule → 2 matchs de poule.
        $this->assertSame(2, GameMatch::where('game_save_id', $save->id)->whereIn('round', ['group_a', 'group_b'])->count());
        $this->assertFalse($this->service->isTournamentOver($save));
    }

    public function test_advance_is_noop_until_current_stage_is_complete(): void
    {
        $save = $this->makeSave(['competition_type' => 'world_cup']);
        foreach (range(1, 4) as $i) {
            $this->makeTeam($save);
        }
        $this->service->generateGroups($save);

        // Aucun match de poule joué → pas de progression.
        $this->service->advance($save);

        $this->assertSame('group', $save->fresh()->state['tournament']['stage']);
        $this->assertSame(0, GameMatch::where('game_save_id', $save->id)->where('round', 'semi')->count());
    }

    public function test_full_tournament_progresses_to_a_champion(): void
    {
        $save = $this->makeSave(['competition_type' => 'world_cup']);
        foreach (range(1, 4) as $i) {
            $this->makeTeam($save);
        }
        $this->service->generateGroups($save);

        // Poules → demies.
        $this->playRounds($save->id, ['group_a', 'group_b']);
        $this->service->advance($save);
        $save->refresh();
        $this->assertSame('semi', $save->state['tournament']['stage']);
        $this->assertSame(2, GameMatch::where('game_save_id', $save->id)->where('round', 'semi')->count());
        $this->assertSame('knockout', $save->phase);

        // Demies → finale.
        $this->playRounds($save->id, ['semi']);
        $this->service->advance($save);
        $save->refresh();
        $this->assertSame('final', $save->state['tournament']['stage']);
        $this->assertSame(1, GameMatch::where('game_save_id', $save->id)->where('round', 'final')->count());

        // Finale → champion.
        $this->playRounds($save->id, ['final']);
        $this->service->advance($save);
        $save->refresh();

        $this->assertSame('done', $save->state['tournament']['stage']);
        $this->assertSame('finished', $save->phase);
        $this->assertNotNull($save->state['tournament']['champion_team_id']);
        $this->assertTrue($this->service->isTournamentOver($save));

        // Le champion est le vainqueur de la finale (domicile, qui a gagné 2-0).
        $finalMatch = GameMatch::where('game_save_id', $save->id)->where('round', 'final')->first();
        $this->assertSame((int) $finalMatch->home_team_id, (int) $save->state['tournament']['champion_team_id']);
    }

    public function test_presentation_returns_null_before_generation(): void
    {
        $save = $this->makeSave(['competition_type' => 'world_cup']);

        $this->assertNull($this->service->presentation($save));
    }
}

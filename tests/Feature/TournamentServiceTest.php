<?php

namespace Tests\Feature;

use App\Enums\GamePhase;
use App\Models\GameSaves\GameMatch;
use App\Services\WorldCup\TournamentService;
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
        $this->assertSame(GamePhase::Group, $save->phase);
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
        $this->assertSame(GamePhase::Knockout, $save->phase);

        // Demies → finale.
        $this->playRounds($save->id, ['semi']);
        $this->service->advance($save);
        $save->refresh();
        $this->assertSame('final', $save->state['tournament']['stage']);
        $this->assertSame(1, GameMatch::where('game_save_id', $save->id)->where('round', 'final')->count());

        // Finale + match pour la 3e place → champion.
        $this->playRounds($save->id, ['final', 'third']);
        $this->service->advance($save);
        $save->refresh();

        $this->assertSame('done', $save->state['tournament']['stage']);
        $this->assertSame(GamePhase::Finished, $save->phase);
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

    /** Monte un tournoi à 4 équipes jusqu'à la fin des poules (les demies sont créées). */
    private function tournamentAtSemis(): \App\Models\GameSaves\GameSave
    {
        $save = $this->makeSave(['competition_type' => 'world_cup']);
        foreach (range(1, 8) as $i) {
            $this->makeTeam($save);
        }
        $this->service->generateGroups($save);
        $this->playRounds($save->id, ['group_a', 'group_b']);
        $this->service->advance($save);

        return $save->refresh();
    }

    public function test_presentation_lists_eliminated_teams_with_their_exit_round(): void
    {
        $save = $this->tournamentAtSemis();

        // Après les poules : les non-qualifiés sont sortis en « group ».
        $eliminated = $this->service->presentation($save)['eliminated'];
        $this->assertCount(4, $eliminated);
        $this->assertSame(['group'], array_values(array_unique($eliminated)));

        // Après la finale : le finaliste battu sort en « final ».
        $this->playRounds($save->id, ['semi']);
        $this->service->advance($save);
        $this->playRounds($save->id, ['final']);
        $this->service->advance($save);
        $eliminated = $this->service->presentation($save->refresh())['eliminated'];
        $loser = (int) GameMatch::where('game_save_id', $save->id)->where('round', 'final')->value('away_team_id');
        $this->assertSame('final', $eliminated[$loser]);
        $this->assertContains('semi', $eliminated);
    }

    public function test_simulated_shootout_favours_the_stronger_side_but_stays_open(): void
    {
        $save = $this->makeSave(['competition_type' => 'world_cup']);
        [$strong] = $this->makeTeamWithSquad($save, [], 11);
        [$weak]   = $this->makeTeamWithSquad($save, [], 11);
        $m = $this->makeMatch($save, $strong, $weak, ['round' => 'final', 'status' => 'played', 'home_score' => 1, 'away_score' => 1]);

        $chance = (new \ReflectionMethod($this->service, 'shootoutHomeChance'))->invoke($this->service, $m);

        $this->assertGreaterThanOrEqual(35, $chance);
        $this->assertLessThanOrEqual(65, $chance);
    }

    public function test_eight_team_tournament_adds_a_third_place_match_with_the_semifinal_losers(): void
    {
        $save = $this->tournamentAtSemis();
        $this->playRounds($save->id, ['semi']);
        $this->service->advance($save);

        $semiLosers = GameMatch::where('game_save_id', $save->id)->where('round', 'semi')->pluck('away_team_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $third = GameMatch::where('game_save_id', $save->id)->where('round', 'third')->get();
        $this->assertCount(1, $third);
        $this->assertSame($semiLosers, collect([$third[0]->home_team_id, $third[0]->away_team_id])->map(fn ($id) => (int) $id)->sort()->values()->all());
        $this->assertSame($third[0]->week, GameMatch::where('game_save_id', $save->id)->where('round', 'final')->value('week'));

        // Tant que la 3e place n'est pas jouée, le tournoi n'est pas terminé.
        $this->playRounds($save->id, ['final']);
        $this->service->advance($save);
        $this->assertSame('final', $save->refresh()->state['tournament']['stage']);

        $this->playRounds($save->id, ['third']);
        $this->service->advance($save);
        $this->assertTrue($this->service->isTournamentOver($save->refresh()));
    }

    public function test_sixteen_team_tournament_runs_through_quarterfinals_without_group_rematches(): void
    {
        $save = $this->makeSave(['competition_type' => 'world_cup']);
        foreach (range(1, 16) as $i) {
            $this->makeTeam($save);
        }
        $this->service->generateGroups($save);
        $groups = $save->refresh()->state['tournament']['groups'];
        $this->assertCount(4, $groups);

        $this->playRounds($save->id, ['group_a', 'group_b', 'group_c', 'group_d']);
        $this->service->advance($save);
        $this->assertSame('quarter', $save->refresh()->state['tournament']['stage']);
        $this->assertSame(4, GameMatch::where('game_save_id', $save->id)->where('round', 'quarter')->count());

        $this->playRounds($save->id, ['quarter']);
        $this->service->advance($save);
        $semis = GameMatch::where('game_save_id', $save->id)->where('round', 'semi')->get();
        $this->assertCount(2, $semis);

        // Chaque demie oppose deux équipes de groupes différents des quarts (A1/B2 contre C1/D2, etc.).
        $groupOf = fn (int $teamId) => collect($groups)->search(fn ($ids) => in_array($teamId, $ids, true));
        $quarters = GameMatch::where('game_save_id', $save->id)->where('round', 'quarter')->orderBy('id')->get();
        $this->assertSame([$quarters[0]->home_team_id, $quarters[1]->home_team_id], [$semis[0]->home_team_id, $semis[0]->away_team_id]);
        $this->assertNotSame($groupOf((int) $semis[0]->home_team_id), $groupOf((int) $semis[0]->away_team_id));

        $this->playRounds($save->id, ['semi']);
        $this->service->advance($save);
        $this->playRounds($save->id, ['final', 'third']);
        $this->service->advance($save);

        $this->assertTrue($this->service->isTournamentOver($save->refresh()));
        $bracket = $this->service->presentation($save)['bracket'];
        $this->assertSame([4, 2, 1, 1], [count($bracket['quarter']), count($bracket['semi']), count($bracket['third']), count($bracket['final'])]);
    }

    public function test_hand_goes_to_the_first_human_that_still_has_a_match(): void
    {
        $save = $this->makeSave(['competition_type' => 'world_cup', 'week' => 5]);
        $out    = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);   // éliminé : pas de match
        $in     = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 2]);
        $ai     = $this->makeTeam($save);
        $save->controlled_game_team_id = $out->id;
        $save->save();
        $this->makeMatch($save, $in, $ai, ['week' => 5, 'round' => 'final']);

        $closing = app(\App\Services\Season\WeekClosingService::class);
        (new \ReflectionMethod($closing, 'passHandToHumanWithMatch'))->invoke($closing, $save);

        $this->assertSame($in->id, (int) $save->refresh()->controlled_game_team_id);
    }
}

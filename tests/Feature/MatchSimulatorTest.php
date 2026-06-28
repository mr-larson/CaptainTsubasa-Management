<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameMatch;
use App\Services\MatchSimulator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Moteur de match (MatchSimulator). Comme la simulation est aléatoire, on
 * teste des INVARIANTS plutôt que des scores exacts : un match simulé est
 * marqué joué, ses scores sont cohérents, le classement est mis à jour à
 * hauteur exacte des buts, et les stats par joueur sont produites.
 */
class MatchSimulatorTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private MatchSimulator $simulator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->simulator = app(MatchSimulator::class);
    }

    public function test_simulating_a_match_sets_score_and_marks_it_played(): void
    {
        $save = $this->makeSave();
        [$home] = $this->makeTeamWithSquad($save);
        [$away] = $this->makeTeamWithSquad($save);
        $match  = $this->makeMatch($save, $home, $away);

        $this->simulator->simulateMatchesCollection(GameMatch::where('id', $match->id)->get());

        $match->refresh();
        $this->assertSame('played', $match->status);
        $this->assertNotNull($match->home_score);
        $this->assertNotNull($match->away_score);
        $this->assertGreaterThanOrEqual(0, (int) $match->home_score);
        $this->assertGreaterThanOrEqual(0, (int) $match->away_score);

        // match_stats produit avec une entrée par joueur.
        $this->assertIsArray($match->match_stats);
        $this->assertArrayHasKey('players', $match->match_stats);
        $this->assertNotEmpty($match->match_stats['players']);
    }

    public function test_simulation_updates_standings_to_exact_goal_totals(): void
    {
        $save = $this->makeSave();
        [$home] = $this->makeTeamWithSquad($save);
        [$away] = $this->makeTeamWithSquad($save);
        $match  = $this->makeMatch($save, $home, $away);

        $this->simulator->simulateMatchesCollection(GameMatch::where('id', $match->id)->get());

        $match->refresh();
        $home->refresh();
        $away->refresh();

        $hs = (int) $match->home_score;
        $as = (int) $match->away_score;

        // Conservation des buts dans le classement.
        $this->assertSame($hs, (int) $home->goals_for);
        $this->assertSame($as, (int) $home->goals_against);
        $this->assertSame($as, (int) $away->goals_for);
        $this->assertSame($hs, (int) $away->goals_against);

        // Cohérence résultat ↔ V/N/D (exactement un point d'incrément par équipe).
        if ($hs > $as) {
            $this->assertSame(1, (int) $home->wins);
            $this->assertSame(1, (int) $away->losses);
        } elseif ($hs < $as) {
            $this->assertSame(1, (int) $away->wins);
            $this->assertSame(1, (int) $home->losses);
        } else {
            $this->assertSame(1, (int) $home->draws);
            $this->assertSame(1, (int) $away->draws);
        }
    }

    public function test_simulate_other_matches_leaves_played_match_untouched(): void
    {
        $save = $this->makeSave();
        [$a] = $this->makeTeamWithSquad($save);
        [$b] = $this->makeTeamWithSquad($save);
        [$c] = $this->makeTeamWithSquad($save);
        [$d] = $this->makeTeamWithSquad($save);

        // Semaine 1 : deux matchs. On « joue » le premier à la main.
        $played = $this->makeMatch($save, $a, $b, ['status' => 'played', 'home_score' => 2, 'away_score' => 1]);
        $other  = $this->makeMatch($save, $c, $d);

        $this->simulator->simulateOtherMatchesOfWeek($played);

        // L'autre match est simulé...
        $this->assertSame('played', $other->fresh()->status);
        // ...mais le match déjà joué garde son score saisi.
        $this->assertSame(2, (int) $played->fresh()->home_score);
        $this->assertSame(1, (int) $played->fresh()->away_score);
    }

    public function test_only_scheduled_matches_are_simulated(): void
    {
        $save = $this->makeSave();
        [$home] = $this->makeTeamWithSquad($save);
        [$away] = $this->makeTeamWithSquad($save);
        $alreadyPlayed = $this->makeMatch($save, $home, $away, ['status' => 'played', 'home_score' => 5, 'away_score' => 0]);

        $this->simulator->simulateMatchesCollection(GameMatch::where('id', $alreadyPlayed->id)->get());

        // Inchangé : la collection ne re-simule pas un match déjà joué.
        $alreadyPlayed->refresh();
        $this->assertSame(5, (int) $alreadyPlayed->home_score);
        $this->assertSame(0, (int) $alreadyPlayed->away_score);
        $this->assertSame(0, (int) $home->fresh()->wins, 'Pas de double comptage au classement.');
    }
}

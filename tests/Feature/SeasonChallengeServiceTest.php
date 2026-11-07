<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Services\SeasonChallengeService;
use App\Services\SeasonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Défis de saison (SeasonChallengeService) : tirage de 5 objectifs par équipe,
 * mesure en direct, verdict + primes + classement des défis en fin de saison.
 */
class SeasonChallengeServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private SeasonChallengeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SeasonChallengeService::class);
    }

    /** Monde à 4 équipes avec effectif, la 1re contrôlée. @return list<GameTeam> */
    private function world(GameSave $save): array
    {
        $teams = [];
        for ($i = 0; $i < 4; $i++) {
            [$t] = $this->makeTeamWithSquad($save, ['is_controlled' => $i === 0, 'budget' => 1000]);
            $teams[] = $t;
        }
        $save->controlled_game_team_id = $teams[0]->id;
        $save->save();

        return $teams;
    }

    /** Remplace les objectifs tirés d'une équipe par une liste maîtrisée. */
    private function forceObjectives(GameSave $save, GameTeam $team, array $objectives): void
    {
        $state = $save->state;
        $state['challenges']['teams'][$team->id]['objectives'] = array_map(fn ($o) => $o + [
            'family' => 'results',
            'label'  => $o['key'],
            'points' => SeasonChallengeService::TIERS[$o['tier']]['points'],
            'reward' => SeasonChallengeService::TIERS[$o['tier']]['reward'],
        ], $objectives);
        $save->state = $state;
        $save->save();
    }

    public function test_disabled_for_world_cup_and_when_option_off(): void
    {
        $this->assertTrue($this->service->isEnabled($this->makeSave()));
        $this->assertFalse($this->service->isEnabled($this->makeSave(['competition_type' => 'world_cup'])));

        $off = $this->makeSave();
        $off->setConfig(['season_challenges_enabled' => false]);
        $this->assertFalse($this->service->isEnabled($off->fresh()));
    }

    public function test_draws_five_objectives_per_team_one_per_family_with_mixed_tiers(): void
    {
        $save  = $this->makeSave();
        $teams = $this->world($save);

        $this->service->ensureSeasonChallenges($save);
        $data = $save->fresh()->state['challenges'];

        $this->assertSame(1, $data['season']);
        $this->assertCount(4, $data['teams']);

        foreach ($teams as $team) {
            $objectives = $data['teams'][$team->id]['objectives'];
            $this->assertCount(SeasonChallengeService::PER_SEASON, $objectives);
            $this->assertEqualsCanonicalizing(array_keys(SeasonChallengeService::FAMILIES), array_column($objectives, 'family'));
            $this->assertEqualsCanonicalizing(['easy', 'easy', 'medium', 'medium', 'hard'], array_column($objectives, 'tier'));
            foreach ($objectives as $o) {
                $this->assertContains($o['key'], SeasonChallengeService::FAMILIES[$o['family']]['keys']);
                $this->assertGreaterThanOrEqual(0, $o['target']);
            }
        }
    }

    public function test_draw_is_stable_for_the_same_season(): void
    {
        $save = $this->makeSave();
        $this->world($save);

        $this->service->ensureSeasonChallenges($save);
        $first = $save->fresh()->state['challenges']['teams'];

        // Un nouvel appel ne retire pas ; un tirage à neuf redonne les mêmes défis.
        $this->service->ensureSeasonChallenges($save->fresh());
        $this->assertEquals($first, $save->fresh()->state['challenges']['teams']);

        $fresh = $save->fresh();
        $state = $fresh->state;
        unset($state['challenges']);
        $fresh->state = $state;
        $fresh->save();
        $this->service->ensureSeasonChallenges($fresh);

        $again = $fresh->fresh()->state['challenges']['teams'];
        foreach ($first as $id => $entry) {
            $this->assertEquals(array_column($entry['objectives'], 'key'), array_column($again[$id]['objectives'], 'key'));
        }
    }

    public function test_waits_for_squads_before_drawing(): void
    {
        $save = $this->makeSave();
        $this->makeTeam($save); // pas d'effectif (draft en cours)

        $this->service->ensureSeasonChallenges($save);

        $this->assertArrayNotHasKey('challenges', $save->fresh()->state ?? []);
    }

    public function test_presentation_tracks_live_progress(): void
    {
        $save = $this->makeSave();
        [$me, $b] = $this->world($save);
        $this->service->ensureSeasonChallenges($save);
        $save = $save->fresh();

        $this->forceObjectives($save, $me, [
            ['key' => 'wins_min',          'tier' => 'easy',   'target' => 1],
            ['key' => 'goals_against_max', 'tier' => 'medium', 'target' => 1],
            ['key' => 'clean_sheets_min',  'tier' => 'hard',   'target' => 3],
        ]);

        $this->makeMatch($save, $me, $b, ['status' => 'played', 'home_score' => 2, 'away_score' => 0]);
        $me->update(['goals_for' => 2, 'goals_against' => 0, 'wins' => 1]);

        $p = $this->service->presentation($save->fresh());
        $byKey = collect($p['objectives'])->keyBy('key');

        $this->assertSame('done', $byKey['wins_min']['status']);
        $this->assertSame('on_track', $byKey['goals_against_max']['status']);
        $this->assertSame('off_track', $byKey['clean_sheets_min']['status']);
        $this->assertSame(1, $byKey['clean_sheets_min']['value']);
        $this->assertSame(1, $p['points_secured']);
        $this->assertSame(6, $p['points_max']);
    }

    public function test_season_end_pays_rewards_and_ranks_challenges(): void
    {
        $save = $this->makeSave();
        [$me, $b, $c, $d] = $this->world($save);
        $this->service->ensureSeasonChallenges($save);
        $save = $save->fresh();

        // Moi : 1 défi facile réussi (1 victoire), 1 difficile raté (2 nuls d'affilée requis 5).
        $this->forceObjectives($save, $me, [
            ['key' => 'wins_min',        'tier' => 'easy', 'target' => 1],
            ['key' => 'unbeaten_streak', 'tier' => 'hard', 'target' => 5],
        ]);
        // B : 1 défi difficile réussi (encaisser au plus 3 buts).
        $this->forceObjectives($save->fresh(), $b, [
            ['key' => 'goals_against_max', 'tier' => 'hard', 'target' => 3],
        ]);
        // C et D : rien de réussissable.
        $this->forceObjectives($save->fresh(), $c, [['key' => 'wins_min', 'tier' => 'easy', 'target' => 10]]);
        $this->forceObjectives($save->fresh(), $d, [['key' => 'wins_min', 'tier' => 'easy', 'target' => 10]]);

        $save = $save->fresh();
        $this->makeMatch($save, $me, $c, ['status' => 'played', 'home_score' => 1, 'away_score' => 0]);
        $me->update(['wins' => 1, 'goals_for' => 1]);
        $c->update(['losses' => 1, 'goals_against' => 1]);
        $b->update(['goals_against' => 2]);

        $standings = app(SeasonService::class)->sortStandings(GameTeam::where('game_save_id', $save->id)->get());
        $rows = $this->service->evaluateSeasonEnd($save, $standings);

        $this->assertSame($b->id, $rows[0]['team_id']); // 3 pts
        $this->assertSame(3, $rows[0]['points']);
        $this->assertSame($me->id, $rows[1]['team_id']); // 1 pt
        $this->assertSame(1, $rows[1]['points']);
        $this->assertSame(1, $rows[1]['achieved']);
        $this->assertSame(2, $rows[1]['total']);

        // Détail gardé pour l'humain seulement.
        $this->assertNotNull($rows[1]['objectives']);
        $this->assertNull($rows[0]['objectives']);

        // Primes versées.
        $this->assertSame(1000 + 150, $me->fresh()->budget);
        $this->assertSame(1000 + 500, $b->fresh()->budget);
        $this->assertSame(1000, $c->fresh()->budget);
    }

    public function test_budget_objective_is_judged_before_league_prizes(): void
    {
        $save = $this->makeSave();
        [$me] = $this->world($save);
        $this->service->ensureSeasonChallenges($save);
        $save = $save->fresh();

        // 1000 GL en caisse, objectif 2000 GL : la prime de classement (≥ 4800 GL)
        // ne doit pas le faire valider.
        $this->forceObjectives($save, $me, [['key' => 'budget_min', 'tier' => 'easy', 'target' => 2000]]);

        $recap = app(SeasonService::class)->endSeason($save->fresh());

        $mine = collect($recap['challenges'])->firstWhere('team_id', $me->id);
        $this->assertSame(0, $mine['achieved']);
        $this->assertSame('failed', $mine['objectives'][0]['status']);
    }

    public function test_signings_are_counted_at_signature_even_if_the_player_leaves(): void
    {
        $save = $this->makeSave();
        [$me, $other] = $this->world($save); // contrats de l'effectif : avant le tirage → non comptés
        $this->service->ensureSeasonChallenges($save);
        $save = $save->fresh();
        $this->forceObjectives($save, $me, [['key' => 'signings_min', 'tier' => 'medium', 'target' => 2]]);

        $recrue = $this->makeContract($save, $me, $this->makePlayer($save), ['start_week' => 3]);
        $this->makeContract($save, $me, $this->makePlayer($save), ['start_week' => 4]);
        $recrue->delete(); // libérée : reste comptée

        $p = $this->service->presentation($save->fresh());
        $this->assertSame(2, $p['objectives'][0]['value']);
        $this->assertSame('done', $p['objectives'][0]['status']);
        $this->assertSame(0, $other->fresh()->season_signings);
    }

    public function test_draft_contracts_of_a_new_season_are_not_counted(): void
    {
        $save = $this->makeSave();
        [$me] = $this->world($save);
        $this->service->ensureSeasonChallenges($save);

        // Saison suivante, défis pas encore tirés (draft en cours).
        $save = $save->fresh();
        $save->season = 2;
        $save->save();
        $this->makeContract($save, $me, $this->makePlayer($save));

        $this->assertSame(0, $me->fresh()->season_signings);
    }

    public function test_top_scorer_counts_only_goals_scored_for_the_club(): void
    {
        $save = $this->makeSave();
        [$me, $b] = $this->world($save);
        $this->service->ensureSeasonChallenges($save);
        $save = $save->fresh();
        $this->forceObjectives($save, $me, [['key' => 'top_scorer_min', 'tier' => 'easy', 'target' => 3]]);

        $striker = $this->makePlayer($save);
        $lineup  = fn (array $ids) => ['formation' => null, 'players' => array_map(fn ($id) => ['id' => $id], $ids)];

        // 2 buts pour moi…
        $this->makeMatch($save, $me, $b, ['status' => 'played', 'home_score' => 2, 'away_score' => 0, 'week' => 1, 'match_stats' => [
            'lineups' => ['home' => $lineup([$striker->id]), 'away' => $lineup([])],
            'players' => [$striker->id => ['offense' => ['goals' => 2]]],
        ]]);
        // …puis 5 buts sous les couleurs de B (transféré) : ne comptent pas pour moi.
        $this->makeMatch($save, $b, $me, ['status' => 'played', 'home_score' => 5, 'away_score' => 0, 'week' => 2, 'match_stats' => [
            'lineups' => ['home' => $lineup([$striker->id]), 'away' => $lineup([])],
            'players' => [$striker->id => ['offense' => ['goals' => 5]]],
        ]]);

        // Le joueur n'est plus sous contrat chez moi : ses 2 buts restent comptés.
        $p = $this->service->presentation($save->fresh());
        $this->assertSame(2, $p['objectives'][0]['value']);
        $this->assertSame('off_track', $p['objectives'][0]['status']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSave;
use App\Services\Season\CareerObjectiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Boucle de carrière (CareerObjectiveService) : activation selon le mode,
 * mandat de classement, jauge de confiance match après match, et verdict de
 * fin de saison (maintien / licenciement / victoire). Toute la donnée vit dans
 * state['career'].
 */
class CareerObjectiveServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private CareerObjectiveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CareerObjectiveService::class);
    }

    /** Écrit un bloc carrière minimal dans le state. */
    private function setCareer(GameSave $save, array $career): void
    {
        $state = $save->state ?? [];
        $state['career'] = $career;
        $save->state = $state;
        $save->save();
    }

    public function test_is_disabled_for_world_cup_and_when_difficulty_none(): void
    {
        // Mode Ligue par défaut (difficulté standard) → actif.
        $league = $this->makeSave();
        $this->assertTrue($this->service->isEnabled($league));

        // Coupe du Monde → jamais de carrière.
        $cup = $this->makeSave(['competition_type' => 'world_cup']);
        $this->assertFalse($this->service->isEnabled($cup));

        // Difficulté désactivée → pas de carrière.
        $off = $this->makeSave();
        $off->setConfig(['career_difficulty' => 'none']);
        $this->assertFalse($this->service->isEnabled($off->fresh()));
    }

    public function test_ensure_season_mandate_sets_target_rank_from_strength(): void
    {
        $save = $this->makeSave();

        // Équipe contrôlée nettement plus faible qu'une rivale.
        [$weak] = $this->makeTeamWithSquad($save);          // stats 50
        [$strong, $strongPlayers] = $this->makeTeamWithSquad($save);
        $strongPlayers->each(fn ($p) => $p->update(['attack' => 95, 'defense' => 95, 'shot' => 95, 'pass' => 95]));

        $save->controlled_game_team_id = $weak->id;
        $save->save();

        $this->service->ensureSeasonMandate($save);

        $mandate = $save->fresh()->state['career']['mandate'];
        $this->assertSame(1, $mandate['season']);
        $this->assertSame(2, $mandate['expected_rank'], 'La plus faible est attendue 2e sur 2.');
        $this->assertSame(2, $mandate['target_rank']);   // offset standard = 0
    }

    public function test_mandate_sets_four_pillars_scaled_by_difficulty(): void
    {
        $targets = [];
        foreach (['survival', 'standard', 'conquest'] as $difficulty) {
            $save = $this->makeSave();
            $save->setConfig(['career_difficulty' => $difficulty]);
            [$team] = $this->makeTeamWithSquad($save, ['budget' => 1000]);
            $this->makeTeamWithSquad($save);
            $save->controlled_game_team_id = $team->id;
            $save->save();

            $this->service->ensureSeasonMandate($save->fresh());
            $pillars = $save->fresh()->state['career']['mandate']['pillars'];

            $this->assertGreaterThan(0, $pillars['strength']['baseline']);
            $this->assertSame(1000, $pillars['budget']['baseline']);
            $targets[$difficulty] = $pillars;
        }

        // Plus la difficulté monte, plus les piliers sont exigeants.
        $this->assertEqualsWithDelta([1.0, 2.0, 3.0], array_map(fn ($d) => $targets[$d]['strength']['target'] - $targets[$d]['strength']['baseline'], ['survival', 'standard', 'conquest']), 0.01);
        $this->assertSame([55, 60, 66], array_map(fn ($d) => $targets[$d]['morale']['target'], ['survival', 'standard', 'conquest']));
        $this->assertTrue($targets['survival']['budget']['target'] < $targets['standard']['budget']['target']);
        $this->assertTrue($targets['standard']['budget']['target'] < $targets['conquest']['budget']['target']);
        $this->assertSame($targets['standard']['rank']['expected'], $targets['standard']['rank']['target'], 'Standard : pas de décalage de rang.');
    }

    public function test_after_week_win_raises_confidence(): void
    {
        $save = $this->makeSave();
        $controlled = $this->makeTeam($save, ['is_controlled' => true]);
        $opp        = $this->makeTeam($save);
        $save->controlled_game_team_id = $controlled->id;
        $save->save();
        $this->setCareer($save, ['confidence' => 50, 'status' => 'active', 'mandate' => ['season' => 1, 'target_rank' => 1, 'label' => 'x']]);

        $this->makeMatch($save, $controlled, $opp, ['week' => 1, 'status' => 'played', 'home_score' => 3, 'away_score' => 0]);

        $this->service->afterWeek($save, 1);

        // Victoire = +5 (aucune modulation : l'adversaire n'a pas d'effectif noté).
        $this->assertSame(55, (int) $save->fresh()->state['career']['confidence']);
    }

    public function test_after_week_loss_can_trigger_mid_season_firing(): void
    {
        $save = $this->makeSave();
        $controlled = $this->makeTeam($save, ['is_controlled' => true]);
        $opp        = $this->makeTeam($save);
        $save->controlled_game_team_id = $controlled->id;
        $save->save();
        $this->setCareer($save, ['confidence' => 3, 'status' => 'active', 'mandate' => ['season' => 1, 'target_rank' => 1, 'label' => 'x']]);

        $this->makeMatch($save, $controlled, $opp, ['week' => 1, 'status' => 'played', 'home_score' => 0, 'away_score' => 2]);

        $this->service->afterWeek($save, 1);

        $career = $save->fresh()->state['career'];
        $this->assertSame(0, (int) $career['confidence']);
        $this->assertSame('fired', $career['status']);
        $this->assertSame('mid_season', $career['fired_reason']);
    }

    public function test_evaluate_season_end_rewards_meeting_the_objective(): void
    {
        $save = $this->makeSave();
        $controlled = $this->makeTeam($save, ['is_controlled' => true]);
        $rival      = $this->makeTeam($save);
        $save->controlled_game_team_id = $controlled->id;
        $save->save();
        $this->setCareer($save, [
            'confidence' => 50, 'status' => 'active',
            'mandate' => ['season' => 1, 'target_rank' => 2, 'label' => 'Top 2'],
            'titles_required' => 2, 'titles_won' => 0,
        ]);

        // Classement final : équipe contrôlée championne (rang 1 ≤ objectif 2).
        $standings = collect([$controlled, $rival]);
        $verdict = $this->service->evaluateSeasonEnd($save, $standings);

        $this->assertTrue($verdict['met']);
        $this->assertTrue($verdict['champion']);
        $this->assertSame('retained', $verdict['outcome']); // 1 titre < 2 requis
        // win_bonus standard (18) + dépassement (2-1)*4 = 22.
        $this->assertSame(72, (int) $save->fresh()->state['career']['confidence']);
        $this->assertSame(1, (int) $save->fresh()->state['career']['titles_won']);
    }

    public function test_season_end_scores_the_four_pillars(): void
    {
        $save = $this->makeSave();
        [$controlled, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'budget' => 2000]);
        $rival = $this->makeTeam($save);
        $save->controlled_game_team_id = $controlled->id;
        $save->save();
        $players->each(fn ($p) => $p->update(['morale' => 70]));
        $strength = (float) $this->service->teamStrengths($save)->get($controlled->id);

        $this->setCareer($save, [
            'confidence' => 50, 'status' => 'active',
            'mandate' => ['season' => 1, 'target_rank' => 1, 'label' => 'Titre', 'pillars' => [
                'rank'     => ['target' => 1, 'expected' => 1],
                'strength' => ['baseline' => $strength, 'target' => $strength + 10],  // raté
                'morale'   => ['target' => 60],                                         // atteint (70)
                'budget'   => ['baseline' => 1000, 'target' => 1500],                   // atteint (2000)
            ]],
            'titles_required' => 5, 'titles_won' => 0,
        ]);

        $verdict = $this->service->evaluateSeasonEnd($save, collect([$controlled, $rival]));

        // Standard : classement 40 + moral 20 + budget 20 = 80 (la force ratée ne rapporte rien).
        $this->assertSame(80, $verdict['score']);
        $this->assertTrue($verdict['met']);
        $this->assertFalse($verdict['pillars']['strength']['met']);
        $this->assertTrue($verdict['pillars']['morale']['met']);
        // 18 + round(8 * 20/40) + 4 (champion) = 26.
        $this->assertSame(26, $verdict['delta']);
        $this->assertSame(76, (int) $save->fresh()->state['career']['confidence']);
    }

    public function test_season_end_with_low_score_costs_confidence(): void
    {
        $save = $this->makeSave();
        [$controlled, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'budget' => 100]);
        $rivals = [$this->makeTeam($save), $this->makeTeam($save), $this->makeTeam($save)];
        $save->controlled_game_team_id = $controlled->id;
        $save->save();
        $players->each(fn ($p) => $p->update(['morale' => 30]));

        $this->setCareer($save, [
            'confidence' => 60, 'status' => 'active',
            'mandate' => ['season' => 1, 'target_rank' => 1, 'label' => 'Titre', 'pillars' => [
                'rank'     => ['target' => 1, 'expected' => 1],
                'strength' => ['baseline' => 50, 'target' => 99],
                'morale'   => ['target' => 60],
                'budget'   => ['baseline' => 1000, 'target' => 1500],
            ]],
            'titles_required' => 5, 'titles_won' => 0,
        ]);

        $verdict = $this->service->evaluateSeasonEnd($save, collect([...$rivals, $controlled]));

        $this->assertSame(0, $verdict['score']);
        $this->assertFalse($verdict['met']);
        // Standard : 22 * (1 + (60-0)/60) = 44.
        $this->assertSame(-44, $verdict['delta']);
        $this->assertSame(16, (int) $save->fresh()->state['career']['confidence']);
    }

    public function test_career_is_won_after_the_required_successful_seasons_without_any_title(): void
    {
        $save = $this->makeSave();
        [$controlled, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'budget' => 2000]);
        $a = $this->makeTeam($save);
        $b = $this->makeTeam($save);
        $save->controlled_game_team_id = $controlled->id;
        $save->save();
        $players->each(fn ($p) => $p->update(['morale' => 70]));
        $strength = (float) $this->service->teamStrengths($save)->get($controlled->id);

        $mandate = ['season' => 1, 'target_rank' => 2, 'label' => 'Top 2', 'pillars' => [
            'rank'     => ['target' => 2, 'expected' => 2],
            'strength' => ['baseline' => $strength, 'target' => $strength],
            'morale'   => ['target' => 60],
            'budget'   => ['baseline' => 1000, 'target' => 1500],
        ]];
        $this->setCareer($save, [
            'confidence' => 50, 'status' => 'active', 'win_mode' => 'seasons',
            'mandate' => $mandate, 'titles_required' => 99, 'titles_won' => 0,
            'seasons_required' => 2, 'seasons_won' => 0,
        ]);

        // 2e sur 3 : objectif atteint, mais pas de titre → 1 saison réussie sur 2.
        $verdict = $this->service->evaluateSeasonEnd($save, collect([$a, $controlled, $b]));
        $this->assertTrue($verdict['met']);
        $this->assertFalse($verdict['champion']);
        $this->assertSame('retained', $verdict['outcome']);
        $this->assertSame(1, $verdict['seasons_won']);

        // Saison suivante réussie → victoire, sans jamais avoir gagné le championnat.
        $state = $save->fresh()->state;
        $state['career']['mandate'] = $mandate;
        $save->state = $state;
        $save->save();
        $verdict = $this->service->evaluateSeasonEnd($save->fresh(), collect([$a, $controlled, $b]));

        $this->assertSame('won', $verdict['outcome']);
        $this->assertSame(0, (int) $save->fresh()->state['career']['titles_won']);
        $this->assertTrue($this->service->isGameOver($save->fresh()));
    }

    public function test_failed_season_does_not_count_toward_the_victory(): void
    {
        $save = $this->makeSave();
        [$controlled] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'budget' => 10]);
        $rivals = [$this->makeTeam($save), $this->makeTeam($save)];
        $save->controlled_game_team_id = $controlled->id;
        $save->save();
        $this->setCareer($save, [
            'confidence' => 80, 'status' => 'active', 'win_mode' => 'seasons',
            'mandate' => ['season' => 1, 'target_rank' => 1, 'label' => 'Titre', 'pillars' => [
                'rank'     => ['target' => 1, 'expected' => 1],
                'strength' => ['baseline' => 50, 'target' => 99],
                'morale'   => ['target' => 99],
                'budget'   => ['baseline' => 1000, 'target' => 5000],
            ]],
            'seasons_required' => 1, 'seasons_won' => 0,
        ]);

        $verdict = $this->service->evaluateSeasonEnd($save, collect([...$rivals, $controlled]));

        $this->assertFalse($verdict['met']);
        $this->assertSame(0, $verdict['seasons_won']);
        $this->assertSame('retained', $verdict['outcome']);
    }

    public function test_new_mandate_switches_the_save_to_season_based_victory(): void
    {
        $save = $this->makeSave();
        [$team] = $this->makeTeamWithSquad($save);
        $this->makeTeamWithSquad($save);
        $save->controlled_game_team_id = $team->id;
        $save->save();

        $this->service->ensureSeasonMandate($save->fresh());

        $presentation = $this->service->presentation($save->fresh());
        $this->assertSame('seasons', $presentation['win_mode']);
        $this->assertSame(4, $presentation['seasons_required']);   // standard
    }

    public function test_evaluate_season_end_wins_career_when_titles_reached(): void
    {
        $save = $this->makeSave();
        $controlled = $this->makeTeam($save, ['is_controlled' => true]);
        $rival      = $this->makeTeam($save);
        $save->controlled_game_team_id = $controlled->id;
        $save->save();
        $this->setCareer($save, [
            'confidence' => 50, 'status' => 'active',
            'mandate' => ['season' => 1, 'target_rank' => 1, 'label' => 'Titre'],
            'titles_required' => 1, 'titles_won' => 0,
        ]);

        $verdict = $this->service->evaluateSeasonEnd($save, collect([$controlled, $rival]));

        $this->assertSame('won', $verdict['outcome']);
        $this->assertTrue($this->service->isGameOver($save->fresh()));
    }

    public function test_evaluate_season_end_fires_on_collapsed_confidence(): void
    {
        $save = $this->makeSave();
        $controlled = $this->makeTeam($save, ['is_controlled' => true]);
        $r1 = $this->makeTeam($save);
        $r2 = $this->makeTeam($save);
        $r3 = $this->makeTeam($save);
        $save->controlled_game_team_id = $controlled->id;
        $save->save();
        $this->setCareer($save, [
            'confidence' => 5, 'status' => 'active',
            'mandate' => ['season' => 1, 'target_rank' => 1, 'label' => 'Titre'],
            'titles_required' => 2, 'titles_won' => 0,
        ]);

        // Objectif titre mais l'équipe finit dernière (rang 4).
        $verdict = $this->service->evaluateSeasonEnd($save, collect([$r1, $r2, $r3, $controlled]));

        $this->assertFalse($verdict['met']);
        $this->assertSame('fired', $verdict['outcome']);
        $this->assertSame('season_end', $save->fresh()->state['career']['fired_reason']);
    }

    public function test_reset_mandate_clears_it_for_next_season(): void
    {
        $save = $this->makeSave();
        $this->setCareer($save, ['confidence' => 50, 'status' => 'active', 'mandate' => ['season' => 1, 'target_rank' => 1]]);

        $this->service->resetMandateForNewSeason($save);

        $this->assertNull($save->fresh()->state['career']['mandate']);
    }
}

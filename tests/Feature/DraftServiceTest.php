<?php

namespace Tests\Feature;

use App\Enums\GamePhase;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Services\Draft\DraftService;
use App\Services\Players\MoraleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Draft (DraftService) : initialisation, picks en serpent (snake), règles de
 * validité (budget, joueur déjà sous contrat, rancune envers le coach), clôture
 * d'équipe et finalisation (lineups + phase season). Très stateful via
 * state['draft'].
 */
class DraftServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private DraftService $service;

    public function test_corrected_internationals_respect_draft_visibility_in_existing_saves(): void
    {
        $save = $this->makeSave();
        $babington = $this->makePlayer($save, [
            'firstname' => 'Juan', 'lastname' => 'Babington', 'origin' => 'captain_tsubasa', 'nationality' => 'Japon',
        ]);
        $haan = $this->makePlayer($save, [
            'firstname' => '', 'lastname' => 'Haan', 'origin' => 'captain_tsubasa', 'nationality' => 'Japon',
        ]);
        $japanese = $this->makePlayer($save, ['origin' => 'captain_tsubasa', 'nationality' => 'Japon']);

        $migration = require database_path('migrations/2026_11_10_145000_correct_babington_and_haan_nationalities.php');
        $migration->up();

        $this->assertSame('Argentine', $babington->fresh()->nationality);
        $this->assertSame('Pays-Bas', $haan->fresh()->nationality);

        $pool = fn () => GamePlayer::where('game_save_id', $save->id)->visibleForConfig($save)->orderBy('id')->pluck('id')->all();
        $save->setConfig(['internationals_visible' => false]);
        $this->assertSame([$japanese->id], $pool());
        $save->setConfig(['internationals_visible' => true]);
        $this->assertSame([$babington->id, $haan->id, $japanese->id], $pool());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DraftService::class);
    }

    public function test_init_draft_opens_a_separate_budget_for_each_team(): void
    {
        $save = $this->makeSave(['phase' => 'intersaison_draft']);
        $a = $this->makeTeam($save, ['budget' => 500, 'is_controlled' => true, 'human_seat' => 1]);
        $b = $this->makeTeam($save, ['budget' => 100000]);
        $save->setConfig(['draft_budget' => 6000]);

        $this->service->initDraft($save, [$a->id, $b->id]);

        $this->assertSame(500, (int) $a->fresh()->budget);
        $this->assertSame(100000, (int) $b->fresh()->budget);

        $draft = $save->fresh()->state['draft'];
        $this->assertSame([$a->id => 6000, $b->id => 10000], $draft['budgets']);
        $this->assertSame([$a->id, $b->id], $draft['order']);
        $this->assertSame(0, $draft['current_pick_index']);
        $this->assertSame(1, $draft['round']);
        $this->assertFalse($draft['completed']);
    }

    public function test_execute_pick_signs_player_and_advances_turn(): void
    {
        $save = $this->makeSave();
        $a = $this->makeTeam($save, ['budget' => 500]);
        $b = $this->makeTeam($save, ['budget' => 100000]);
        $this->service->initDraft($save, [$a->id, $b->id]);

        $player = $this->makePlayer($save, ['cost' => 1000]);

        $pick = $this->service->executePick($save, $player->id);

        $this->assertNotNull($pick);
        $this->assertSame($player->id, $pick['player_id']);

        // Contrat créé pour l'équipe au tour de jeu (a).
        $this->assertTrue(
            GameContract::where('game_player_id', $player->id)->where('game_team_id', $a->id)->exists()
        );
        // Numéro de maillot attribué.
        $this->assertSame(1, (int) $player->fresh()->number);

        // Coût = adjusted_cost × longueur de saison × réduction (1000 × 2 × 0.5 = 1000).
        $this->assertSame(500, (int) $a->fresh()->budget);
        $this->assertSame(9000, DraftService::budgetFor($save->fresh(), $a->fresh()));

        // La main passe à l'équipe suivante.
        $this->assertSame($b->id, $this->service->getCurrentTeamId($save->fresh()));
    }

    public function test_draft_budget_defaults_to_the_difficulty_and_accepts_an_override(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $secondHuman = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 2]);
        $aiTeam = $this->makeTeam($save);

        foreach (['conquest' => 7000, 'standard' => 8500, 'survival' => 10000] as $difficulty => $budget) {
            $save->setConfig(['career_difficulty' => $difficulty]);
            $this->service->initDraft($save, [$team->id, $secondHuman->id, $aiTeam->id]);
            $this->assertSame($budget, DraftService::budgetFor($save, $team));
            $this->assertSame($budget, DraftService::budgetFor($save, $secondHuman));
            $this->assertSame(10000, DraftService::budgetFor($save, $aiTeam));
        }

        $save->setConfig(['draft_budget' => 6500, 'ai_draft_budget' => 12000]);
        $this->service->initDraft($save, [$team->id, $aiTeam->id]);
        $this->assertSame(6500, DraftService::budgetFor($save, $team));
        $this->assertSame(12000, DraftService::budgetFor($save, $aiTeam));
    }

    public function test_new_drafts_discount_modest_salaries_and_older_drafts_keep_their_prices(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save);

        $this->assertSame(600, $this->service->pickCost($save, 40, 30));

        $this->service->initDraft($save, [$team->id]);
        $this->assertSame(300, $this->service->pickCost($save, 40, 30));
        $this->assertSame(525, $this->service->pickCost($save, 50, 30));
        $this->assertSame(1200, $this->service->pickCost($save, 80, 30));
    }

    public function test_pick_order_snakes_between_rounds(): void
    {
        $save = $this->makeSave();
        $a = $this->makeTeam($save, ['budget' => 100000]);
        $b = $this->makeTeam($save, ['budget' => 100000]);
        $this->service->initDraft($save, [$a->id, $b->id]);

        $this->assertSame($a->id, $this->service->getCurrentTeamId($save->fresh()), 'Round 1 commence par a.');

        // a puis b piochent → fin du round 1.
        $this->service->executePick($save->fresh(), $this->makePlayer($save, ['cost' => 1000])->id);
        $this->service->executePick($save->fresh(), $this->makePlayer($save, ['cost' => 1000])->id);

        // Round 2 (pair) : ordre inversé → b repique en premier.
        $this->assertSame($b->id, $this->service->getCurrentTeamId($save->fresh()));
    }

    public function test_cannot_draft_already_contracted_player(): void
    {
        $save = $this->makeSave();
        $a = $this->makeTeam($save, ['budget' => 100000]);
        $this->makeTeam($save, ['budget' => 100000]);
        $this->service->initDraft($save, [$a->id]);

        $taken = $this->makePlayer($save);
        $this->makeContract($save, $a, $taken);

        $this->assertNull($this->service->executePick($save, $taken->id));
    }

    public function test_cannot_draft_when_budget_too_low(): void
    {
        $save = $this->makeSave();
        $a = $this->makeTeam($save, ['budget' => 100000]);
        $b = $this->makeTeam($save, ['budget' => 100000]);
        $this->service->initDraft($save, [$a->id, $b->id]);

        $expensive = $this->makePlayer($save, ['cost' => 11000]);

        $this->assertNull($this->service->executePick($save->fresh(), $expensive->id));
        $this->assertFalse(GameContract::where('game_player_id', $expensive->id)->exists());
    }

    public function test_player_refuses_controlled_team_but_not_ai(): void
    {
        $save = $this->makeSave();
        $human = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1]);
        $ai    = $this->makeTeam($save);
        $save->controlled_game_team_id = $human->id;
        $save->save();

        // Joueur fâché contre le coach (affinité sous le seuil de refus).
        $angry = $this->makePlayer($save, ['coach_affinity' => MoraleService::AFFINITY_REFUSAL_THRESHOLD - 10]);

        $this->assertTrue($this->service->playerRefusesTeam($save, $angry, $human->id), 'Refuse l\'équipe humaine.');
        $this->assertFalse($this->service->playerRefusesTeam($save, $angry, $ai->id), 'Accepte une équipe IA.');
    }

    public function test_finish_team_draft_requires_minimum_squad(): void
    {
        $save = $this->makeSave();
        $a = $this->makeTeam($save, ['budget' => 100000]);
        $this->makeTeam($save, ['budget' => 100000]);
        $this->service->initDraft($save, [$a->id]);

        // Effectif sous le minimum : refus.
        [$small] = $this->makeTeamWithSquad($save, [], DraftService::MIN_SQUAD - 1);
        $this->assertFalse($this->service->finishTeamDraft($save, $small->id));
        $this->assertNotContains($small->id, $save->fresh()->state['draft']['finished_teams']);

        // Effectif au minimum : accepté (marqué terminé).
        [$ok] = $this->makeTeamWithSquad($save, [], DraftService::MIN_SQUAD);
        $this->service->finishTeamDraft($save, $ok->id);
        $this->assertContains($ok->id, $save->fresh()->state['draft']['finished_teams']);
    }

    public function test_forced_finish_closes_an_exhausted_team_under_minimum(): void
    {
        $save = $this->makeSave();
        [$small] = $this->makeTeamWithSquad($save, [], DraftService::MIN_SQUAD - 3);
        $this->service->initDraft($save, [$small->id]);

        // Sans `force`, le draft resterait bloqué sur cette équipe.
        $this->assertTrue($this->service->finishTeamDraft($save, $small->id, force: true));

        $draft = $save->fresh()->state['draft'];
        $this->assertContains($small->id, $draft['exhausted_teams']);
        $this->assertTrue($draft['completed']);
    }

    public function test_human_without_affordable_player_can_finish_under_minimum(): void
    {
        $save = $this->makeSave(['phase' => 'draft']);
        [$human] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1, 'budget' => 0], DraftService::MIN_SQUAD - 2);
        $save->update(['controlled_game_team_id' => $human->id]);
        $this->service->initDraft($save, [$human->id]);
        $this->makePlayer($save, ['cost' => 11000]); // joueur libre, trop cher pour l'enveloppe

        $this->assertFalse($this->service->hasAffordablePlayer($save->fresh(), $human->fresh()));

        $this->actingAs(\App\Models\User::find($save->user_id))
            ->postJson(route('game-saves.draft.finish', $save))
            ->assertOk()
            ->assertJson(['completed' => true]);

        $this->assertContains($human->id, $save->fresh()->state['draft']['finished_teams'] ?? []);
    }

    public function test_finalize_draft_builds_lineups_and_starts_season(): void
    {
        $save = $this->makeSave(['phase' => 'intersaison_draft']);
        [$team] = $this->makeTeamWithSquad($save, ['budget' => 500], 14);
        $this->service->initDraft($save, [$team->id]);

        $this->service->finalizeDraft($save);
        $save->refresh();

        $this->assertSame(GamePhase::Season, $save->phase);
        $this->assertSame(500, (int) $team->fresh()->budget);
        $this->assertArrayNotHasKey('budgets', $save->state['draft']);

        $this->service->purgeFinishedDraft($save->fresh());
        $this->assertArrayNotHasKey('draft', $save->fresh()->state);

        // Lineup persisté avec une formation et 11 titulaires.
        $lineup = $save->state['lineup'][$team->id];
        $this->assertArrayHasKey('formation', $lineup);
        $this->assertCount(11, $lineup['slots']);

        $this->assertSame(11, GameContract::where('game_team_id', $team->id)->where('is_starter', true)->count());
    }

    public function test_affordable_player_check_resolves_the_human_teams_once_not_per_player(): void
    {
        $save  = $this->makeSave(['phase' => 'draft']);
        $human = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1, 'budget' => 100000]);
        $save->update(['controlled_game_team_id' => $human->id]);
        foreach (range(1, 15) as $_) {
            $this->makePlayer($save, ['cost' => 10, 'coach_affinity' => -100]); // tous en rupture avec le coach
        }
        app(\App\Services\Draft\DraftService::class)->initDraft($save->fresh(), [$human->id]);
        $save = $save->fresh();

        $teamReads = 0;
        \Illuminate\Support\Facades\DB::listen(function ($q) use (&$teamReads) {
            if (preg_match('/^\s*select/i', $q->sql) && str_contains($q->sql, '`is_controlled`')) $teamReads++;
        });

        $affordable = app(\App\Services\Draft\DraftService::class)->hasAffordablePlayer($save, $human);

        $this->assertFalse($affordable, 'Tous les joueurs refusent une équipe humaine.');
        $this->assertLessThanOrEqual(1, $teamReads, 'Les équipes humaines sont lues une fois, pas une fois par joueur.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameContract;
use App\Services\DraftService;
use App\Services\MoraleService;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DraftService::class);
    }

    public function test_init_draft_grants_budget_bonus_and_sets_state(): void
    {
        $save = $this->makeSave(['phase' => 'intersaison_draft']);
        $a = $this->makeTeam($save, ['budget' => 100000]);
        $b = $this->makeTeam($save, ['budget' => 100000]);

        $this->service->initDraft($save, [$a->id, $b->id]);

        $this->assertSame(100000 + DraftService::DRAFT_BONUS, (int) $a->fresh()->budget);

        $draft = $save->fresh()->state['draft'];
        $this->assertSame([$a->id, $b->id], $draft['order']);
        $this->assertSame(0, $draft['current_pick_index']);
        $this->assertSame(1, $draft['round']);
        $this->assertFalse($draft['completed']);
    }

    public function test_execute_pick_signs_player_and_advances_turn(): void
    {
        $save = $this->makeSave();
        $a = $this->makeTeam($save, ['budget' => 100000]);
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
        $this->assertSame(100000 + DraftService::DRAFT_BONUS - 1000, (int) $a->fresh()->budget);

        // La main passe à l'équipe suivante.
        $this->assertSame($b->id, $this->service->getCurrentTeamId($save->fresh()));
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

        $a->update(['budget' => 10]); // budget dérisoire
        $expensive = $this->makePlayer($save, ['cost' => 5000]);

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

    public function test_finalize_draft_builds_lineups_and_starts_season(): void
    {
        $save = $this->makeSave(['phase' => 'intersaison_draft']);
        [$team] = $this->makeTeamWithSquad($save, [], 14);

        $this->service->finalizeDraft($save);
        $save->refresh();

        $this->assertSame('season', $save->phase);

        // Lineup persisté avec une formation et 11 titulaires.
        $lineup = $save->state['lineup'][$team->id];
        $this->assertArrayHasKey('formation', $lineup);
        $this->assertCount(11, $lineup['slots']);

        $this->assertSame(11, GameContract::where('game_team_id', $team->id)->where('is_starter', true)->count());
    }
}

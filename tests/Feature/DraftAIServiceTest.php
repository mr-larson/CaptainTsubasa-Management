<?php

namespace Tests\Feature;

use App\Enums\TeamStyle;
use App\Models\GameSaves\GameTeam;
use App\Services\DraftAIService;
use App\Services\DraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * IA de draft (DraftAIService) : choix d'un joueur libre selon les besoins de
 * poste / le budget, et décision d'arrêt selon la philosophie de gestion.
 */
class DraftAIServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private DraftAIService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DraftAIService::class);
    }

    public function test_returns_null_when_no_free_player(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['budget' => 100000]);
        $this->makeTeam($save);

        $this->assertNull($this->service->chooseBestPlayer($save, $team));
    }

    public function test_picks_an_available_free_player(): void
    {
        $save = $this->makeSave();
        $team = $this->makeTeam($save, ['budget' => 100000]);
        $this->makeTeam($save);

        $free = $this->makePlayer($save, ['position' => 'GK', 'cost' => 1000, 'origin' => 'original']);

        $chosen = $this->service->chooseBestPlayer($save, $team);

        $this->assertSame($free->id, $chosen, 'Un seul libre disponible → il est choisi.');
    }

    public function test_ignores_already_contracted_players(): void
    {
        $save = $this->makeSave();
        $team  = $this->makeTeam($save, ['budget' => 100000]);
        $other = $this->makeTeam($save, ['budget' => 100000]);

        // Seul libre = un GK ; un autre joueur est déjà sous contrat ailleurs.
        $contracted = $this->makePlayer($save, ['position' => 'MID', 'cost' => 1000]);
        $this->makeContract($save, $other, $contracted);
        $free = $this->makePlayer($save, ['position' => 'GK', 'cost' => 1000, 'origin' => 'original']);

        $this->assertSame($free->id, $this->service->chooseBestPlayer($save, $team));
    }

    public function test_should_finish_draft_respects_minimum_and_maximum(): void
    {
        $team = new GameTeam(['management_philosophy' => TeamStyle::PHILOSOPHY_COLLECTIVE]);

        // Sous le minimum : jamais terminé.
        $this->assertFalse($this->service->shouldFinishDraft($team, DraftService::MIN_SQUAD - 1, 100000));
        // Au maximum : toujours terminé.
        $this->assertTrue($this->service->shouldFinishDraft($team, DraftService::MAX_SQUAD, 100000));
    }

    public function test_should_finish_draft_varies_with_philosophy(): void
    {
        $economist = new GameTeam(['management_philosophy' => TeamStyle::PHILOSOPHY_ECONOMIST]);
        $collective = new GameTeam(['management_philosophy' => TeamStyle::PHILOSOPHY_COLLECTIVE]);

        // À 14 joueurs : l'économe s'arrête, le collectif veut plus large.
        $this->assertTrue($this->service->shouldFinishDraft($economist, 14, 100000));
        $this->assertFalse($this->service->shouldFinishDraft($collective, 14, 100000));
    }
}

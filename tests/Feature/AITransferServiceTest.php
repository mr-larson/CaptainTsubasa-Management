<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Services\AITransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Mercato IA (AITransferService::recruitForWeek) : les équipes gérées par l'IA
 * comblent leur effectif dans la limite du budget et du quota hebdomadaire,
 * sans toucher aux équipes humaines.
 */
class AITransferServiceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private AITransferService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AITransferService::class);
    }

    /** Pool de joueurs libres (sans contrat) couvrant tous les postes. */
    private function seedFreeAgents(\App\Models\GameSaves\GameSave $save): void
    {
        $positions = ['GK', 'GK', 'DEF', 'DEF', 'DEF', 'MID', 'MID', 'MID', 'ATT', 'ATT'];
        foreach ($positions as $pos) {
            $this->makePlayer($save, ['position' => $pos, 'cost' => 1000, 'origin' => 'original']);
        }
    }

    public function test_ai_team_signs_players_within_weekly_limit(): void
    {
        $save = $this->makeSave();
        $ai   = $this->makeTeam($save, ['budget' => 100000]); // effectif vide
        $this->makeTeam($save); // 2e équipe → longueur de saison cohérente
        $this->seedFreeAgents($save);

        $this->service->recruitForWeek($save);

        // Au plus 2 recrutements par semaine.
        $signings = GameContract::where('game_save_id', $save->id)->where('game_team_id', $ai->id)->count();
        $this->assertGreaterThan(0, $signings);
        $this->assertLessThanOrEqual(2, $signings);

        // Budget débité.
        $this->assertLessThan(100000, (int) $ai->fresh()->budget);
    }

    public function test_controlled_team_is_never_recruited_for(): void
    {
        $save    = $this->makeSave();
        $human   = $this->makeTeam($save, ['is_controlled' => true, 'human_seat' => 1, 'budget' => 100000]);
        $ai      = $this->makeTeam($save, ['budget' => 100000]);
        $save->controlled_game_team_id = $human->id;
        $save->save();

        $this->seedFreeAgents($save);

        $this->service->recruitForWeek($save);

        $this->assertSame(0, GameContract::where('game_save_id', $save->id)->where('game_team_id', $human->id)->count(),
            "L'équipe humaine ne doit jamais être renforcée par l'IA.");
        $this->assertGreaterThan(0, GameContract::where('game_save_id', $save->id)->where('game_team_id', $ai->id)->count());
    }

    public function test_full_squad_is_not_reinforced(): void
    {
        $save = $this->makeSave();
        [$ai] = $this->makeTeamWithSquad($save, ['budget' => 100000], 18); // effectif déjà plein
        $this->makeTeam($save);
        $this->seedFreeAgents($save);

        $before = GameContract::where('game_save_id', $save->id)->where('game_team_id', $ai->id)->count();
        $this->service->recruitForWeek($save);
        $after = GameContract::where('game_save_id', $save->id)->where('game_team_id', $ai->id)->count();

        $this->assertSame($before, $after, 'Aucun recrutement au-delà de 18 joueurs.');
        $this->assertSame(18, $after);
    }

    public function test_team_without_budget_does_not_recruit(): void
    {
        $save = $this->makeSave();
        $ai   = $this->makeTeam($save, ['budget' => 0]); // budget nul
        $this->makeTeam($save);
        $this->seedFreeAgents($save);

        $this->service->recruitForWeek($save);

        $this->assertSame(0, GameContract::where('game_save_id', $save->id)->where('game_team_id', $ai->id)->count());
    }

    public function test_recruitment_does_not_reuse_already_contracted_players(): void
    {
        $save = $this->makeSave();
        $ai   = $this->makeTeam($save, ['budget' => 100000]);
        $this->makeTeam($save);
        $this->seedFreeAgents($save);

        $this->service->recruitForWeek($save);

        // Aucun joueur ne se retrouve avec deux contrats actifs.
        $contracted = GameContract::where('game_save_id', $save->id)->pluck('game_player_id');
        $this->assertSame($contracted->count(), $contracted->unique()->count());
    }
}

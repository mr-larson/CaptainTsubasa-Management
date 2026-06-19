<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\Team;
use App\Models\User;
use App\Services\DraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotSeatDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_start_marks_multiple_human_teams(): void
    {
        $user  = User::factory()->create();
        $teams = Team::factory()->count(4)->create();

        $this->actingAs($user)
            ->post(route('game-saves.start'), [
                'period'    => 'college',
                'game_mode' => 'draft',
                'team_ids'  => [$teams[0]->id, $teams[2]->id],
            ])
            ->assertRedirect();

        $save = GameSave::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('draft', $save->phase);

        $controlled = GameTeam::where('game_save_id', $save->id)
            ->where('is_controlled', true)
            ->orderBy('human_seat')
            ->get();

        $this->assertCount(2, $controlled, 'Le draft doit accepter 2 équipes humaines.');
        $this->assertSame(1, $controlled[0]->human_seat);
        $this->assertSame(2, $controlled[1]->human_seat);

        // L'ordre du draft contient toutes les équipes de la save.
        $order = $save->state['draft']['order'] ?? [];
        $this->assertCount(4, $order);
    }

    public function test_is_human_turn_recognizes_any_human_team(): void
    {
        $user = User::factory()->create();

        $save = GameSave::create([
            'user_id'   => $user->id,
            'team_id'   => null,
            'period'    => 'college',
            'season'    => 1,
            'week'      => 1,
            'phase'     => 'draft',
            'game_mode' => 'draft',
            'state'     => null,
        ]);

        $human1 = $this->gameTeam($save, 'Humain 1', true, 1);
        $human2 = $this->gameTeam($save, 'Humain 2', true, 2);
        $ai     = $this->gameTeam($save, 'IA', false, null);

        $save->controlled_game_team_id = $human1->id;

        $draftService = app(DraftService::class);

        // Horloge sur le 2e humain → c'est bien un tour humain.
        $save->state = ['draft' => $this->draftState([$ai->id, $human2->id, $human1->id], 1)];
        $save->save();
        $this->assertTrue($draftService->isHumanTurn($save->fresh()));

        // Horloge sur l'IA → pas un tour humain.
        $save->state = ['draft' => $this->draftState([$ai->id, $human2->id, $human1->id], 0)];
        $save->save();
        $this->assertFalse($draftService->isHumanTurn($save->fresh()));
    }

    private function draftState(array $order, int $pickIndex): array
    {
        return [
            'order'              => $order,
            'current_pick_index' => $pickIndex,
            'round'              => 1,
            'picks'              => [],
            'completed'          => false,
            'finished_teams'     => [],
        ];
    }

    private function gameTeam(GameSave $save, string $name, bool $controlled, ?int $seat): GameTeam
    {
        return GameTeam::create([
            'game_save_id'  => $save->id,
            'base_team_id'  => null,
            'is_controlled' => $controlled,
            'human_seat'    => $seat,
            'name'          => $name,
            'budget'        => 100000,
            'wins'          => 0,
            'draws'         => 0,
            'losses'        => 0,
        ]);
    }
}

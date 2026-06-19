<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotSeatStartTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_marks_multiple_controlled_teams_with_seats(): void
    {
        $user  = User::factory()->create();
        $teams = Team::factory()->count(4)->create();

        $human1 = $teams[0];
        $human2 = $teams[2];

        $this->actingAs($user)
            ->post(route('game-saves.start'), [
                'period'    => 'college',
                'game_mode' => 'prebuilt',
                'team_ids'  => [$human1->id, $human2->id],
            ])
            ->assertRedirect();

        $save = GameSave::where('user_id', $user->id)->firstOrFail();

        // Équipe propriétaire / active = siège 1.
        $this->assertSame($human1->id, $save->team_id);

        // Exactement deux équipes humaines, avec sièges 1 et 2 dans l'ordre choisi.
        $controlled = GameTeam::where('game_save_id', $save->id)
            ->where('is_controlled', true)
            ->orderBy('human_seat')
            ->get();

        $this->assertCount(2, $controlled);
        $this->assertSame($human1->id, $controlled[0]->base_team_id);
        $this->assertSame(1, $controlled[0]->human_seat);
        $this->assertSame($human2->id, $controlled[1]->base_team_id);
        $this->assertSame(2, $controlled[1]->human_seat);

        // controlled_game_team_id pointe sur le siège 1.
        $this->assertSame($controlled[0]->id, $save->controlled_game_team_id);

        // Le helper renvoie les deux équipes, ordonnées.
        $this->assertSame(
            [$controlled[0]->id, $controlled[1]->id],
            $save->controlledGameTeamIds(),
        );
    }

    public function test_start_with_single_team_id_stays_mono_manager(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $this->actingAs($user)
            ->post(route('game-saves.start'), [
                'period'    => 'college',
                'game_mode' => 'prebuilt',
                'team_id'   => $team->id,
            ])
            ->assertRedirect();

        $save = GameSave::where('user_id', $user->id)->firstOrFail();

        $controlled = GameTeam::where('game_save_id', $save->id)
            ->where('is_controlled', true)
            ->get();

        $this->assertCount(1, $controlled);
        $this->assertSame($team->id, $controlled[0]->base_team_id);
        $this->assertSame(1, $controlled[0]->human_seat);
        $this->assertSame($controlled[0]->id, $save->controlled_game_team_id);
    }
}

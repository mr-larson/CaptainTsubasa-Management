<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class GameRequestValidationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_config_update_rejects_a_min_above_the_max_already_in_force(): void
    {
        $save = $this->makeSave();
        $save->setConfig(['training_gain_max' => 3]);

        $this->actingAs(User::find($save->user_id))
            ->put(route('game-saves.config.update', $save), ['training_gain_min' => 7])
            ->assertSessionHasErrors('training_gain_max');

        $this->assertNotSame(7, (int) $save->fresh()->getConfig('training_gain_min'));
    }

    public function test_config_update_accepts_a_consistent_range(): void
    {
        $save = $this->makeSave();

        $this->actingAs(User::find($save->user_id))
            ->put(route('game-saves.config.update', $save), ['training_gain_min' => 2, 'training_gain_max' => 6])
            ->assertSessionHasNoErrors();

        $this->assertSame(6, (int) $save->fresh()->getConfig('training_gain_max'));
    }

    public function test_formation_rules_share_one_source_of_truth(): void
    {
        $save = $this->makeSave();
        [$team] = $this->makeTeamWithSquad($save, ['is_controlled' => true]);
        $save->update(['controlled_game_team_id' => $team->id]);
        $user = User::find($save->user_id);

        $this->actingAs($user)
            ->post(route('game-saves.lineup.formation', $save), ['team_id' => $team->id, 'formation' => '5-2-3-0'])
            ->assertSessionHasErrors('formation');

        $this->actingAs($user)
            ->post(route('game-saves.lineup.formation', $save), ['team_id' => $team->id, 'formation' => '4-3-2-1'])
            ->assertSessionHasNoErrors();
    }

    public function test_a_stranger_gets_403_before_any_validation_runs(): void
    {
        $save     = $this->makeSave();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->post(route('game-saves.lineup.formation', $save), ['formation' => 'not-a-formation'])
            ->assertForbidden();
    }
}

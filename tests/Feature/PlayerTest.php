<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test if a player can be associated with a team.
     */
    public function test_it_can_associate_with_a_team(): void
    {
        // Create a player and a team
        $player = Player::factory()->create();
        $team = Team::factory()->create();

        // Associate player with the team
        $player->team()->associate($team)->save();

        // Check if the player's team_id matches the created team's id
        $this->assertEquals($team->id, $player->team_id);
    }
}

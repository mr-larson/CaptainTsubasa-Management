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

        // La relation joueur <-> équipe passe par la table pivot "contracts" (many-to-many)
        $player->teams()->attach($team->id, [
            'salary'     => 1000,
            'start_date' => now(),
            'end_date'   => now()->addYear(),
        ]);

        // Le joueur est bien rattaché à l'équipe via un contrat
        $this->assertTrue($player->teams()->where('teams.id', $team->id)->exists());
        $this->assertDatabaseHas('contracts', [
            'player_id' => $player->id,
            'team_id'   => $team->id,
        ]);
    }
}

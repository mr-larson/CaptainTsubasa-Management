<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test if a team can be created.
     */
    public function test_it_can_be_created(): void
    {
        // Crée une équipe à l'aide de la factory
        $team = Team::factory()->create();

        // Vérifiez que l'équipe a bien été créée en interrogeant la base de données
        $this->assertDatabaseHas('teams', ['id' => $team->id]);

        // Autres assertions
        $this->assertIsString($team->name);
        $this->assertIsString($team->logo_path);
        $this->assertIsInt($team->budget);
        $this->assertIsInt($team->points);
        $this->assertIsInt($team->wins);
        $this->assertIsInt($team->draws);
        $this->assertIsInt($team->losses);
        $this->assertIsArray($team->team_stats_bonus);
        $this->assertIsArray($team->active_cards);
    }
}

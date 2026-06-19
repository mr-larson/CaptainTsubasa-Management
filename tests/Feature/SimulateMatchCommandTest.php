<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke end-to-end du moteur de match via la commande game:simulate :
 * exerce réellement MatchSimulator + PostMatchProgressionService, et vérifie
 * que le scénario jetable n'est pas persisté.
 */
class SimulateMatchCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_simulates_a_match_and_persists_nothing(): void
    {
        $this->artisan('game:simulate', ['--events' => 0])
            ->assertSuccessful();

        // Scénario jetable : la transaction est annulée, rien ne reste.
        $this->assertDatabaseCount('game_saves', 0);
        $this->assertDatabaseCount('game_matches', 0);
        $this->assertDatabaseCount('game_players', 0);
    }

    public function test_it_rejects_an_invalid_tactical_style(): void
    {
        $this->artisan('game:simulate', ['--home-style' => 'tiki-taka'])
            ->assertFailed();
    }
}

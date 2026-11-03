<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Relances de capitaine : 3 par match, décomptées côté serveur et remises à
 * zéro au lancement du match suivant.
 */
class CaptainRerollTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_rerolls_are_counted_then_reset_for_the_next_match(): void
    {
        $save = $this->makeSave();
        [$human, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        [$ai] = $this->makeTeamWithSquad($save);
        $save->update(['controlled_game_team_id' => $human->id]);
        $this->makeMatch($save, $human, $ai);

        $captain = $players[0]->contracts()->first();
        $captain->update(['is_captain' => true, 'captain_rerolls_remaining' => 1, 'captain_reroll_used_this_action' => true]);

        $user = User::find($save->user_id);

        // Lancement du match : stock remis à 3.
        $this->actingAs($user)->get(route('game-saves.match', $save))->assertOk();
        $this->assertSame(3, (int) $captain->fresh()->captain_rerolls_remaining);

        // Une relance pendant le match est décomptée côté serveur.
        $this->actingAs($user)
            ->postJson(route('game-saves.captain-reroll.use', [$save, $captain]))
            ->assertOk()
            ->assertJson(['success' => true]);
        $this->assertSame(2, (int) $captain->fresh()->captain_rerolls_remaining);
    }
}

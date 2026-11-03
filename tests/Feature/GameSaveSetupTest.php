<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Création de partie : l'étape « choix d'équipe » est servie en GET depuis la
 * session (F5 ou erreur de validation au lancement n'y perdent rien), et la
 * reprise d'une partie en draft ramène au draft.
 */
class GameSaveSetupTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_step_one_redirects_to_a_reloadable_team_selection(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('game-saves.store'), ['period' => 'college', 'game_mode' => 'prebuilt'])
            ->assertRedirect(route('game-saves.setup'));

        // Rechargement : même écran, même réglages.
        $this->actingAs($user)->get(route('game-saves.setup'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('GameSaves/TeamSelection')
                ->where('gameMode', 'prebuilt'));
    }

    public function test_setup_without_step_one_goes_back_to_creation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('game-saves.setup'))
            ->assertRedirect(route('game-saves.create'));
    }

    public function test_play_resumes_an_unfinished_draft(): void
    {
        $save = $this->makeSave(['phase' => 'draft']);

        $this->actingAs(User::find($save->user_id))
            ->get(route('game-saves.Play', $save))
            ->assertRedirect(route('game-saves.draft', $save));
    }
}

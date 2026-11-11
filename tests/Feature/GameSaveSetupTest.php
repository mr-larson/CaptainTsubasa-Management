<?php

namespace Tests\Feature;

use App\Models\GameSaves\GamePlayer;
use App\Models\Player;
use App\Models\Team;
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

    public function test_team_selection_keeps_the_same_initial_stats_after_reload_and_start(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $aiTeam = Team::factory()->create();
        $budgets = ['human_start_budget' => 1750, 'ai_start_budget' => 3250, 'ai_draft_budget' => 11500];
        $player = Player::factory()->create(['cost' => 30, 'stats' => ['speed' => 60]]);
        $team->contracts()->create(['player_id' => $player->id, 'salary' => 30]);

        $this->actingAs($user)
            ->post(route('game-saves.store'), ['period' => 'college', 'game_config' => $budgets])
            ->assertRedirect(route('game-saves.setup'));

        $props = $this->get(route('game-saves.setup'))->viewData('page')['props'];
        $selected = collect($props['teams'])->firstWhere('id', $team->id);
        $preview = $selected['contracts'][0]['player'];
        $selectedIndex = array_search($selected, $props['teams'], true);
        $this->assertSame(1750, $props['humanStartBudget']);
        $this->assertSame(11500, $props['aiDraftBudget']);
        $this->assertGreaterThanOrEqual(50, $preview['speed']);
        $this->assertLessThanOrEqual(70, $preview['speed']);
        $this->get(route('game-saves.setup'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where("teams.{$selectedIndex}.contracts.0.player.speed", $preview['speed'])
            ->where("teams.{$selectedIndex}.contracts.0.player.stamina", $preview['stamina'])
            ->where("teams.{$selectedIndex}.contracts.0.player.morale_preview", $preview['morale_preview'])
            ->where("teams.{$selectedIndex}.contracts.0.player.luck", $preview['luck']));

        $this->post(route('game-saves.start'), [
            'period' => 'college',
            'team_id' => $team->id,
            'setup_token' => $props['setupToken'],
            'game_config' => $props['gameConfig'],
        ])
            ->assertRedirect();

        $this->assertDatabaseHas('game_teams', ['base_team_id' => $team->id, 'budget' => 1750]);
        $this->assertDatabaseHas('game_teams', ['base_team_id' => $aiTeam->id, 'budget' => 3250]);

        $saved = GamePlayer::where('base_player_id', $player->id)->sole();
        $this->assertSame((int) $preview['speed'], (int) $saved->speed);
        $this->assertSame((int) $preview['stamina'], (int) $saved->stamina);
        $this->assertSame((int) $preview['morale_preview'], (int) $saved->morale);
        $this->assertSame((int) $preview['luck'], $saved->luck);
    }

    public function test_play_resumes_an_unfinished_draft(): void
    {
        $save = $this->makeSave(['phase' => 'draft']);

        $this->actingAs(User::find($save->user_id))
            ->get(route('game-saves.Play', $save))
            ->assertRedirect(route('game-saves.draft', $save));
    }
}

<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSave;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class RouteHardeningTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_debug_styles_route_is_gone(): void
    {
        $save = $this->makeSave();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get("/debug/styles/{$save->id}")->assertNotFound();
    }

    public function test_match_demo_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create())->get('/match/demo')->assertForbidden();
    }

    public function test_welcome_reference_data_is_cached(): void
    {
        Cache::forget('welcome:reference');
        Team::factory()->create(['name' => 'Alpha']);

        $this->get('/')->assertOk();
        $this->assertTrue(Cache::has('welcome:reference'));

        Team::factory()->create(['name' => 'Beta']);
        $this->get('/')->assertOk()->assertDontSee('Beta');
    }

    public function test_game_creation_is_refused_past_the_save_limit(): void
    {
        config(['game.max_saves_per_user' => 1]);
        $user = User::factory()->create();
        $team = Team::factory()->create();
        GameSave::create(['user_id' => $user->id, 'period' => 'college', 'season' => 1, 'week' => 1, 'phase' => 'season']);

        $this->actingAs($user)
            ->post(route('game-saves.start'), ['period' => 'college', 'game_mode' => 'prebuilt', 'team_id' => $team->id])
            ->assertSessionHasErrors('game_save');

        $this->assertSame(1, GameSave::where('user_id', $user->id)->count());
    }

    public function test_game_creation_is_throttled(): void
    {
        config(['game.max_saves_per_user' => 1000]);
        $user = User::factory()->create();

        $statuses = [];
        for ($i = 0; $i < 7; $i++) {
            $statuses[] = $this->actingAs($user)->post(route('game-saves.start'), [])->getStatusCode();
        }

        $this->assertSame(429, end($statuses));
        $this->assertNotContains(429, array_slice($statuses, 0, 5));
    }
}

<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameMatch;
use App\Models\Team;
use App\Models\User;
use App\Services\Season\SeasonBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class PlayPreparationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function saveWithTeams(): array
    {
        $save  = $this->makeSave();
        [$human] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        foreach (range(1, 3) as $_) {
            $this->makeTeamWithSquad($save);
        }
        $save->update(['controlled_game_team_id' => $human->id]);

        return [$save->fresh(), User::find($save->user_id)];
    }

    public function test_start_prepares_the_first_week(): void
    {
        $user = User::factory()->create();
        foreach (Team::factory()->count(4)->create() as $team) {
            foreach (\App\Models\Player::factory()->count(12)->create() as $player) {
                \App\Models\Contract::create(['team_id' => $team->id, 'player_id' => $player->id, 'salary' => 10, 'is_starter' => false, 'is_captain' => false]);
            }
        }

        $this->actingAs($user)->post(route('game-saves.start'), [
            'period' => 'college', 'game_mode' => 'prebuilt', 'team_id' => Team::first()->id,
        ])->assertRedirect();

        $save = \App\Models\GameSaves\GameSave::where('user_id', $user->id)->firstOrFail();
        $this->assertGreaterThan(0, GameMatch::where('game_save_id', $save->id)->count());
        $this->assertFalse(app(SeasonBootstrapService::class)->needsPreparation($save->fresh()));
    }

    public function test_play_generates_a_missing_calendar_once_and_then_stops_writing(): void
    {
        [$save, $user] = $this->saveWithTeams();
        $this->assertTrue(app(SeasonBootstrapService::class)->needsPreparation($save));

        $this->actingAs($user)->get(route('game-saves.Play', $save))->assertOk();
        $count = GameMatch::where('game_save_id', $save->id)->count();
        $this->assertGreaterThan(0, $count);

        $this->assertFalse(app(SeasonBootstrapService::class)->needsPreparation($save->fresh()));
        $this->actingAs($user)->get(route('game-saves.Play', $save))->assertOk();
        $this->assertSame($count, GameMatch::where('game_save_id', $save->id)->count());
    }

    public function test_prepare_is_idempotent(): void
    {
        [$save] = $this->saveWithTeams();
        $service = app(SeasonBootstrapService::class);

        $service->prepare($save);
        $count = GameMatch::where('game_save_id', $save->id)->count();
        $service->prepare($save->fresh());

        $this->assertSame($count, GameMatch::where('game_save_id', $save->id)->count());
    }
}

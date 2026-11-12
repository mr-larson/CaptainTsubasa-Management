<?php

namespace Tests\Feature;

use App\Models\GameSaves\GameSave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Toute action qui relit puis réécrit game_saves.state passe par le verrou de
 * save (SELECT … FOR UPDATE) : deux requêtes simultanées ne s'écrasent pas.
 */
class StateWriteLockTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function takesSaveLock(callable $request): bool
    {
        $locked = false;
        DB::listen(function ($query) use (&$locked) {
            if (stripos($query->sql, 'from `game_saves`') !== false && stripos($query->sql, 'for update') !== false) {
                $locked = true;
            }
        });
        $request();

        return $locked;
    }

    public function test_lineup_and_config_writes_take_the_save_lock(): void
    {
        $save = $this->makeSave();
        [$team, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true], 12);
        $save->update(['controlled_game_team_id' => $team->id]);
        $owner = User::find($save->user_id);

        $slots = $players->take(11)->values()->map(fn ($p, $i) => ['slot' => $i + 1, 'player_id' => $p->id])->all();

        $this->assertTrue($this->takesSaveLock(fn () => $this->actingAs($owner)
            ->post(route('game-saves.lineup.update', $save), ['team_id' => $team->id, 'slots' => $slots])
            ->assertRedirect()));
        $this->assertSame($players[0]->id, $save->fresh()->state['lineup'][$team->id]['slots'][1]);

        $this->assertTrue($this->takesSaveLock(fn () => $this->actingAs($owner)
            ->post(route('game-saves.lineup.substitute', $save), [
                'team_id' => $team->id, 'starter_id' => $players[0]->id, 'substitute_id' => $players[11]->id,
            ])->assertRedirect()));
        $this->assertSame($players[11]->id, $save->fresh()->state['lineup'][$team->id]['slots'][1]);

        $this->assertTrue($this->takesSaveLock(fn () => $this->actingAs($owner)
            ->put(route('game-saves.config.update', $save), ['training_gain_min' => 1, 'training_gain_max' => 3])
            ->assertSessionHasNoErrors()));
    }
}

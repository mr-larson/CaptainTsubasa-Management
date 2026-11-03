<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class GamePlayerUpdateTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_update_keeps_short_label_and_cooldown_of_special_moves(): void
    {
        $save   = $this->makeSave();
        $player = $this->makePlayer($save);

        $this->actingAs(User::find($save->user_id))
            ->put(route('game-saves.players.update', [$save, $player]), [
                'firstname'     => 'Tsubasa',
                'lastname'      => 'Ozora',
                'position'      => 'Midfielder',
                'cost'          => 100,
                'special_moves' => [[
                    'key'         => 'drive_shot',
                    'label'       => 'Drive Shot',
                    'short_label' => 'Drive',
                    'mode'        => 'attack',
                    'base_action' => 'shot',
                    'cooldown'    => 4,
                ]],
            ])
            ->assertSessionHasNoErrors();

        $move = $player->fresh()->special_moves[0];
        $this->assertSame('Drive', $move['short_label']);
        $this->assertSame(4, (int) $move['cooldown']);
    }
}

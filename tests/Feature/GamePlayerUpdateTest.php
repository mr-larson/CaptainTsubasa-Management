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

    public function test_store_redirects_to_the_list_with_the_new_player_selected(): void
    {
        $save = $this->makeSave();

        $response = $this->actingAs(User::find($save->user_id))
            ->post(route('game-saves.players.store', $save), [
                'firstname' => 'Kojiro',
                'lastname'  => 'Hyuga',
                'position'  => 'Forward',
                'cost'      => 80,
            ])
            ->assertSessionHasNoErrors();

        $created = \App\Models\GameSaves\GamePlayer::where('game_save_id', $save->id)
            ->where('lastname', 'Hyuga')->firstOrFail();
        $response->assertRedirect(route('game-saves.players.index', [$save, 'selected' => $created->id]));
    }

    public function test_legacy_create_url_opens_the_list_in_creation_mode(): void
    {
        $save = $this->makeSave();
        $user = User::find($save->user_id);

        $this->actingAs($user)
            ->get(route('game-saves.players.create', $save))
            ->assertRedirect(route('game-saves.players.index', [$save, 'create' => 1]));

        $this->actingAs($user)
            ->get(route('game-saves.players.index', [$save, 'create' => 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('GameSaves/GamePlayers/Index')->where('startCreate', true));
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

/**
 * Hot-seat : un derby entre deux humains se joue à deux, et la composition
 * d'une autre équipe (IA ou autre manager) n'est pas modifiable.
 */
class HotSeatMatchAndLineupTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_derby_between_two_humans_is_played_in_two_player_mode(): void
    {
        $save = $this->makeSave();
        [$seat1] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        [$seat2] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 2]);
        $save->update(['controlled_game_team_id' => $seat1->id]);
        $this->makeMatch($save, $seat1, $seat2);

        $response = $this->actingAs(User::find($save->user_id))->get(route('game-saves.match', $save));

        $response->assertOk();
        $this->assertSame('both', $response->viewData('page')['props']['engineConfig']['controlMode']);
    }

    public function test_lineup_of_another_team_cannot_be_changed(): void
    {
        $save = $this->makeSave();
        [$mine]                 = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        [$other, $otherPlayers] = $this->makeTeamWithSquad($save);
        $save->update(['controlled_game_team_id' => $mine->id]);

        $user     = User::find($save->user_id);
        $contract = $otherPlayers[0]->contracts()->first();

        $this->actingAs($user)
            ->post(route('game-saves.lineup.formation', $save), ['team_id' => $other->id, 'formation' => '5-2-2-1'])
            ->assertSessionHasErrors('team_id');

        $this->actingAs($user)
            ->patch(route('game-contracts.toggle-starter', $contract))
            ->assertSessionHasErrors('team_id');

        $this->assertTrue((bool) $contract->fresh()->is_starter);
        $this->assertNotSame('5-2-2-1', $other->fresh()->formation);

        // Sa propre équipe reste modifiable.
        $this->actingAs($user)
            ->post(route('game-saves.lineup.formation', $save), ['team_id' => $mine->id, 'formation' => '5-2-2-1'])
            ->assertSessionHasNoErrors();
        $this->assertSame('5-2-2-1', $mine->fresh()->formation);
    }

    public function test_benching_a_starter_frees_his_lineup_slot(): void
    {
        $save = $this->makeSave();
        [$mine, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1], 12);
        $slots = [];
        foreach ($players->take(11) as $i => $p) {
            $slots[$i + 1] = $p->id;
        }
        $save->update([
            'controlled_game_team_id' => $mine->id,
            'state' => ['lineup' => [$mine->id => ['slots' => $slots]]],
        ]);

        $contract = $players[3]->contracts()->first();
        $this->actingAs(User::find($save->user_id))
            ->patch(route('game-contracts.toggle-starter', $contract))
            ->assertSessionHasNoErrors();

        $saved = $save->fresh()->state['lineup'][$mine->id]['slots'];
        $this->assertNotContains($players[3]->id, array_values($saved), 'Le remplaçant ne doit plus occuper de slot.');
    }
}

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

    public function test_match_includes_only_current_pending_promises_for_starters_and_substitutes(): void
    {
        $save = $this->makeSave();
        [$mine, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true], 12);
        [$opponent] = $this->makeTeamWithSquad($save);
        $save->update(['controlled_game_team_id' => $mine->id]);
        $this->makeMatch($save, $mine, $opponent);
        $base = [
            'game_save_id' => $save->id, 'game_team_id' => $mine->id,
            'type' => 'rest', 'status' => 'pending', 'season' => $save->season,
            'start_week' => 1, 'due_week' => 2, 'target_matches' => 0, 'played_matches' => 0,
        ];
        foreach ([$players[0], $players[11]] as $player) {
            \App\Models\GameSaves\GamePromise::create($base + ['game_player_id' => $player->id]);
        }
        \App\Models\GameSaves\GamePromise::create(array_replace($base, [
            'game_player_id' => $players[1]->id, 'status' => 'kept',
        ]));
        \App\Models\GameSaves\GamePromise::create(array_replace($base, [
            'game_player_id' => $players[2]->id, 'start_week' => 2, 'due_week' => 3,
        ]));
        $response = $this->actingAs(User::find($save->user_id))->get(route('game-saves.match', $save));
        $response->assertOk();
        $payload = collect($response->viewData('page')['props']['engineConfig']['teams']['internal']['players'])->keyBy('id');
        foreach ([$players[0], $players[11]] as $player) {
            $this->assertCount(1, $payload[$player->id]['promises']);
            $this->assertSame('rest', $payload[$player->id]['promises'][0]['type']);
        }
        $this->assertEmpty($payload[$players[1]->id]['promises']);
        $this->assertEmpty($payload[$players[2]->id]['promises']);
    }

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

    public function test_match_does_not_duplicate_a_player_from_a_stale_lineup_on_the_bench(): void
    {
        $save = $this->makeSave();
        [$mine] = $this->makeTeamWithSquad($save, ['is_controlled' => true]);
        [$opponent, $players] = $this->makeTeamWithSquad($save, [], 13);
        $benched = $players[1];
        $replacement = $players[11];
        $fallback = $players[2];
        $benched->contracts()->update(['is_starter' => false]);
        $fallback->contracts()->update(['is_starter' => false]);
        $replacement->contracts()->update(['is_starter' => true]);
        $slots = $players->take(11)->values()->mapWithKeys(fn ($player, $index) => [$index + 1 => $player->id])->all();
        $save->update([
            'controlled_game_team_id' => $mine->id,
            'state' => ['lineup' => [$opponent->id => ['slots' => $slots]]],
        ]);
        $this->makeMatch($save, $mine, $opponent);

        $response = $this->actingAs($save->user)->get(route('game-saves.match', $save))->assertOk();
        $roster = collect($response->viewData('page')['props']['engineConfig']['teams']['external']['players']);

        $this->assertTrue($roster->firstWhere('id', $replacement->id)['is_starter']);
        $ids = $roster->pluck('id')->filter();
        $this->assertSame($ids->count(), $ids->unique()->count());
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
            ->patch(route('game-saves.contracts.toggle-starter', [$save, $contract]))
            ->assertSessionHasErrors('team_id');

        $this->assertTrue((bool) $contract->fresh()->is_starter);
        $this->assertNotSame('5-2-2-1', $other->fresh()->formation);

        // Sa propre équipe reste modifiable.
        $this->actingAs($user)
            ->post(route('game-saves.lineup.formation', $save), ['team_id' => $mine->id, 'formation' => '5-2-2-1'])
            ->assertSessionHasNoErrors();
        $this->assertSame('5-2-2-1', $mine->fresh()->formation);
    }

    public function test_ai_team_lineup_is_editable_when_other_teams_access_is_enabled(): void
    {
        $save = $this->makeSave();
        [$mine]                 = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1]);
        [$rival]                = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 2]);
        [$ai, $aiPlayers]       = $this->makeTeamWithSquad($save);
        $state = $save->state ?? [];
        $state['config'] = array_merge($state['config'] ?? [], ['other_teams_editable' => true]);
        $save->update(['controlled_game_team_id' => $mine->id, 'state' => $state]);

        $user     = User::find($save->user_id);
        $contract = $aiPlayers[0]->contracts()->first();

        // Équipe IA : formation et capitaine modifiables.
        $this->actingAs($user)
            ->post(route('game-saves.lineup.formation', $save), ['team_id' => $ai->id, 'formation' => '5-2-2-1'])
            ->assertSessionHasNoErrors();
        $this->assertSame('5-2-2-1', $ai->fresh()->formation);

        $this->actingAs($user)
            ->patch(route('game-saves.contracts.toggle-captain', [$save, $contract]))
            ->assertSessionHasNoErrors();
        $this->assertTrue((bool) $contract->fresh()->is_captain);

        // L'autre manager humain reste protégé.
        $this->actingAs($user)
            ->post(route('game-saves.lineup.formation', $save), ['team_id' => $rival->id, 'formation' => '5-2-2-1'])
            ->assertSessionHasErrors('team_id');
        $this->assertNotSame('5-2-2-1', $rival->fresh()->formation);
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
            ->patch(route('game-saves.contracts.toggle-starter', [$save, $contract]))
            ->assertSessionHasNoErrors();

        $saved = $save->fresh()->state['lineup'][$mine->id]['slots'];
        $this->assertNotContains($players[3]->id, array_values($saved), 'Le remplaçant ne doit plus occuper de slot.');
    }

    public function test_a_twelfth_starter_is_refused(): void
    {
        $save = $this->makeSave();
        [$mine, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1], 12);
        $save->update(['controlled_game_team_id' => $mine->id]);

        $sub = $players[11]->contracts()->first();
        $sub->update(['is_starter' => false]);
        foreach ($players->take(11) as $p) {
            $p->contracts()->update(['is_starter' => true]);
        }

        $this->actingAs(User::find($save->user_id))
            ->patch(route('game-saves.contracts.toggle-starter', [$save, $sub]))
            ->assertSessionHasErrors('is_starter');

        $this->assertFalse((bool) $sub->fresh()->is_starter);
    }

    public function test_promoting_a_substitute_fills_the_requested_empty_slot(): void
    {
        $save = $this->makeSave(['week' => 2]);
        [$mine, $players] = $this->makeTeamWithSquad($save, ['is_controlled' => true, 'human_seat' => 1], 12);
        $slots = [];
        foreach ($players->take(11) as $i => $p) {
            $slots[$i + 1] = $p->id;
        }
        $players[1]->contracts()->update(['end_week' => 1]);
        $players[8]->contracts()->update(['end_week' => 1]);
        $save->update([
            'controlled_game_team_id' => $mine->id,
            'state' => ['lineup' => [$mine->id => ['slots' => $slots]]],
        ]);

        $contract = $players[11]->contracts()->first();
        $this->actingAs(User::find($save->user_id))
            ->patch(route('game-saves.contracts.toggle-starter', [$save, $contract]), ['slot' => 9])
            ->assertSessionHasNoErrors();

        $saved = $save->fresh()->state['lineup'][$mine->id]['slots'];
        $this->assertSame($players[11]->id, (int) $saved[9], 'Le contrat expiré doit libérer le slot demandé.');
        $this->assertTrue(empty($saved[2]));
    }
}

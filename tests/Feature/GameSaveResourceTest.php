<?php

namespace Tests\Feature;

use App\Http\Resources\GameSaveResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class GameSaveResourceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    private function stateWithEverything(): array
    {
        return [
            'lineup'      => [34 => ['slots' => [1 => 5]], 7 => ['slots' => [1 => 9]]],
            'training'    => ['week' => 3],
            'draft'       => ['picks' => [1, 2, 3]],
            'career'      => ['confidence' => 50],
            'challenges'  => ['season' => 1, 'teams' => []],
            'config'      => ['match_max_turns' => 30],
            'pending_malus' => [['target_team_id' => 1]],
            'contract_departures' => [['player_id' => 1]],
        ];
    }

    public function test_only_whitelisted_state_keys_reach_the_front(): void
    {
        $save = $this->makeSave(['state' => $this->stateWithEverything()]);

        $array = GameSaveResource::make($save->fresh())->toArray();

        $this->assertEqualsCanonicalizing(['lineup', 'training', 'contract_departures'], array_keys($array['state']));
        $this->assertArrayNotHasKey('user_id', $array);
    }

    public function test_lineup_keeps_its_team_id_keys(): void
    {
        $save = $this->makeSave(['state' => $this->stateWithEverything()]);

        $json = json_decode(json_encode(GameSaveResource::make($save->fresh())->toArray()), true);

        $this->assertEqualsCanonicalizing([34, 7], array_map('intval', array_keys($json['state']['lineup'])));
        $this->assertSame([1 => 9], $json['state']['lineup']['7']['slots']);
    }

    public function test_listing_summary_carries_no_state(): void
    {
        $save = $this->makeSave(['state' => $this->stateWithEverything()]);

        $response = $this->actingAs(User::find($save->user_id))->get(route('game-saves.index'))->assertOk();
        $row = $response->viewData('page')['props']['gameSaves'][0];

        $this->assertArrayNotHasKey('state', $row);
        $this->assertSame($save->id, $row['id']);
        $this->assertSame('season', $row['phase']);
    }

    public function test_play_page_receives_the_trimmed_save(): void
    {
        $save = $this->makeSave(['state' => $this->stateWithEverything()]);
        [$team] = $this->makeTeamWithSquad($save, ['is_controlled' => true]);
        $save->update(['controlled_game_team_id' => $team->id]);

        $props = $this->actingAs(User::find($save->user_id))
            ->get(route('game-saves.Play', $save))->assertOk()
            ->viewData('page')['props']['gameSave'];

        foreach (['draft', 'career', 'challenges', 'config', 'pending_malus'] as $serverOnly) {
            $this->assertArrayNotHasKey($serverOnly, $props['state'] ?? []);
        }
        $this->assertArrayHasKey('lineup', $props['state']);
    }
}

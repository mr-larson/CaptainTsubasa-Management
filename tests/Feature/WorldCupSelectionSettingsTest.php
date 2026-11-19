<?php

namespace Tests\Feature;

use App\Enums\Nationality;
use App\Helpers\InitialPlayerStats;
use App\Models\GameSaves\GameSave;
use App\Models\GameSaves\GameTeam;
use App\Models\Player;
use App\Models\User;
use App\Services\WorldCup\NewWorldCupBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorldCupSelectionSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function pool(): array
    {
        DB::table('players')->delete();
        $nations = array_slice(Nationality::ALL, 0, 8);
        foreach ($nations as $nation) {
            foreach (['Goalkeeper' => 1, 'Defender' => 5, 'Midfielder' => 4, 'Forward' => 3] as $position => $count) {
                Player::factory()->count($count)->create(['nationality' => $nation, 'position' => $position]);
            }
        }
        return $nations;
    }

    public function test_preview_stats_and_each_human_formation_are_preserved_when_the_tournament_starts(): void
    {
        [$main, $co] = $this->pool();
        $builder = app(NewWorldCupBuilder::class);
        $seed = 654321;
        $config = ['initial_morale_random' => true, 'initial_stamina_random' => true, 'initial_speed_random' => true, 'luck_random' => true];
        $previews = [$main => $builder->squadPreview($main, $config, $seed), $co => $builder->squadPreview($co, $config, $seed)];
        $this->assertSame($previews[$main], $builder->squadPreview($main, $config, $seed));
        $ids = fn ($nation) => array_column($previews[$nation]['players'], 'id');
        $save = $builder->build(User::factory()->create(), [
            'nation' => $main, 'period' => 'college', 'game_config' => $config, 'player_stats_seed' => $seed,
            'player_ids' => $ids($main), 'formation' => '4-2-1-3',
            'co_nations' => [$co], 'co_player_ids' => [$co => $ids($co)], 'co_formations' => [$co => '3-2-3-2'],
        ]);
        foreach ([$main => '4-2-1-3', $co => '3-2-3-2'] as $nation => $shape) {
            $team = GameTeam::where('game_save_id', $save->id)->where('name', $nation)->sole();
            $this->assertSame($shape, $team->formation);
            $this->assertSame($shape, $save->gameState()->lineups()[$team->id]['formation']);
            $previewById = collect($previews[$nation]['players'])->keyBy('id');
            foreach ($team->contracts()->with('gamePlayer')->get() as $contract) {
                $player = $contract->gamePlayer;
                foreach (['speed', 'stamina', 'morale', 'luck'] as $stat) {
                    $this->assertSame($previewById[$player->base_player_id][$stat], $player->$stat, "$nation / $stat");
                }
            }
        }
    }

    public function test_setup_keeps_the_same_initial_stats_on_refresh_and_start_uses_the_session_seed(): void
    {
        [$nation] = $this->pool();
        $user = User::factory()->create();
        $seed = 123456;
        $this->actingAs($user)->withoutMiddleware(ThrottleRequests::class)->withSession(['game_save_setup' => [
            'period' => 'college', 'competition_type' => 'world_cup', 'player_stats_seed' => $seed,
        ]]);
        $seen = [];
        for ($i = 0; $i < 2; $i++) {
            $this->get(route('game-saves.setup', ['nation' => $nation]))->assertOk()->assertInertia(function (Assert $page) use (&$seen) {
                $page->component('GameSaves/NationSelection')->where('squad.players', function ($players) use (&$seen) {
                    $seen[] = collect($players)->map(fn ($p) => collect($p)->only(['id', 'stamina', 'morale', 'luck', 'speed'])->all())->all();
                    return true;
                });
            });
        }
        $this->assertSame($seen[0], $seen[1]);
        $ids = Player::where('nationality', $nation)->pluck('id')->all();
        $this->post(route('game-saves.start-world-cup'), ['nation' => $nation, 'period' => 'college', 'competition_type' => 'world_cup', 'player_ids' => $ids, 'formation' => '4-2-1-3'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $save = GameSave::where('user_id', $user->id)->sole();
        $this->assertSame($seed, $save->state['player_stats_seed']);
        $saved = $save->controlledGameTeam->contracts()->with('gamePlayer')->first()->gamePlayer;
        $this->assertSame(collect($seen[0])->firstWhere('id', $saved->base_player_id)['morale'], $saved->morale);
    }

    public function test_formation_preview_uses_the_requested_shape_and_rejects_unknown_shapes(): void
    {
        [$nation] = $this->pool();
        $this->actingAs(User::factory()->create())->withoutMiddleware(ThrottleRequests::class);
        $data = ['nation' => $nation, 'player_ids' => Player::where('nationality', $nation)->pluck('id')->all()];
        $this->postJson(route('game-saves.world-cup-lineup-preview'), $data + ['formation' => '3-2-3-2'])
            ->assertOk()->assertJsonPath('lineup.formation', '3-2-3-2');
        $this->postJson(route('game-saves.world-cup-lineup-preview'), $data + ['formation' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('formation');
        $this->post(route('game-saves.start-world-cup'), $data + ['period' => 'college', 'competition_type' => 'world_cup', 'formation' => 'invalid'])
            ->assertSessionHasErrors('formation');
    }

    public function test_initial_stats_respect_disabled_random_options(): void
    {
        [$nation] = $this->pool();
        $withMove = Player::where('nationality', $nation)->firstOrFail();
        $moves = [['key' => 'test_save', 'label' => 'Arrêt spécial', 'mode' => 'defense', 'cooldown' => 3]];
        $withMove->update(['special_moves' => $moves]);
        $config = ['initial_morale_random' => false, 'initial_stamina_random' => false, 'initial_speed_random' => false, 'luck_random' => false];
        $preview = app(NewWorldCupBuilder::class)->squadPreview($nation, $config, 987);
        $this->assertEquals($moves, collect($preview['players'])->firstWhere('id', $withMove->id)['special_moves']);
        foreach ($preview['players'] as $row) {
            $base = Player::findOrFail($row['id']);
            $expected = InitialPlayerStats::forPlayer(new GameSave(['state' => ['config' => $config]]), 987, $base->id, (int) ($base->stats['speed'] ?? 50));
            foreach ($expected as $key => $value) $this->assertSame($value, $row[$key]);
            $this->assertSame(100, $row['stamina']);
            $this->assertSame(60, $row['morale']);
        }
    }
    public function test_manual_starter_swaps_and_bench_changes_are_saved_for_each_manager(): void
    {
        [$main, $co] = $this->pool();
        $ids = fn ($nation) => Player::where('nationality', $nation)->orderBy('id')->pluck('id')->all();
        $mainIds = $ids($main);
        $coIds = $ids($co);
        $slots = array_combine(range(1, 11), array_slice($mainIds, 0, 11));
        [$slots[2], $slots[3]] = [$slots[3], $slots[2]];
        $slots[10] = $mainIds[12];
        $coSlots = array_combine(range(1, 11), array_slice($coIds, 0, 11));
        $coSlots[5] = $coIds[12];
        $save = app(NewWorldCupBuilder::class)->build(User::factory()->create(), [
            'nation' => $main, 'period' => 'college', 'player_ids' => $mainIds,
            'formation' => '4-2-1-3', 'lineup_slots' => $slots,
            'co_nations' => [$co], 'co_player_ids' => [$co => $coIds],
            'co_formations' => [$co => '3-2-3-2'], 'co_lineup_slots' => [$co => $coSlots],
        ]);
        foreach ([$main => $slots, $co => $coSlots] as $nation => $expected) {
            $team = GameTeam::where('game_save_id', $save->id)->where('name', $nation)->sole();
            $contracts = $team->contracts()->with('gamePlayer')->get();
            $this->assertCount(11, $contracts->where('is_starter', true));
            $baseIds = $contracts->mapWithKeys(fn ($c) => [$c->game_player_id => $c->gamePlayer->base_player_id]);
            foreach ($save->gameState()->lineupSlots($team->id) as $slot => $savedId) {
                $this->assertSame($expected[$slot], $baseIds[$savedId]);
            }
        }
    }

    public function test_manual_lineups_cannot_inject_unselected_players_or_invalid_slots(): void
    {
        [$nation, $other] = $this->pool();
        $ids = Player::where('nationality', $nation)->orderBy('id')->pluck('id')->all();
        $slots = array_combine(range(1, 11), array_slice($ids, 0, 11));
        $foreign = Player::where('nationality', $other)->value('id');
        $badKeys = $slots;
        unset($badKeys[1]);
        $badKeys[12] = $ids[0];
        $this->actingAs(User::factory()->create())->withoutMiddleware(ThrottleRequests::class);
        $data = ['nation' => $nation, 'period' => 'college', 'competition_type' => 'world_cup', 'player_ids' => $ids];
        foreach ([array_slice($slots, 0, 10, true), array_replace($slots, [11 => $slots[10]]), array_replace($slots, [11 => $foreign]), $badKeys] as $bad) {
            $this->post(route('game-saves.start-world-cup'), $data + ['lineup_slots' => $bad])->assertSessionHasErrors();
        }
        $this->assertSame(0, GameSave::count());
    }

}

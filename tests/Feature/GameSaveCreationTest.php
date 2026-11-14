<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\GameSaves\GameContract;
use App\Models\GameSaves\GamePlayer;
use App\Models\GameSaves\GameSave;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Services\Transfers\AmateurMarketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameSaveCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_bulk_inserts_players_contracts_and_jersey_numbers(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $other = Team::factory()->create();

        foreach ([$team, $other] as $t) {
            foreach (Player::factory()->count(13)->create() as $player) {
                Contract::create([
                    'team_id' => $t->id, 'player_id' => $player->id, 'salary' => 10,
                    'is_starter' => false, 'is_captain' => false,
                ]);
            }
        }

        $this->actingAs($user)
            ->post(route('game-saves.start'), [
                'period' => 'college', 'game_mode' => 'prebuilt', 'team_id' => $team->id,
            ])
            ->assertRedirect();

        $save = GameSave::where('user_id', $user->id)->firstOrFail();

        // Hors amateurs du marché (ensureFloor), sans base_player_id.
        $this->assertSame(26, GamePlayer::where('game_save_id', $save->id)->whereNotNull('base_player_id')->count());
        $this->assertSame(26, GameContract::where('game_save_id', $save->id)->count());
        $this->assertSame(0, GamePlayer::where('game_save_id', $save->id)->whereNull('luck')->count());

        // Chaque équipe : numéros 1-11 pour les titulaires, 12+ pour les remplaçants, sans doublon.
        foreach (GameContract::where('game_save_id', $save->id)->get()->groupBy('game_team_id') as $contracts) {
            $numbers = $contracts->map(fn ($c) => GamePlayer::find($c->game_player_id)->number)->all();
            $this->assertCount(count($numbers), array_unique($numbers));
            $this->assertNotContains(null, $numbers);
        }
    }

    public function test_failed_start_leaves_no_half_built_save(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        Player::factory()->count(3)->create();

        $this->mock(AmateurMarketService::class)
            ->shouldReceive('ensureFloor')->andThrow(new \RuntimeException('boom'));

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('game-saves.start'), [
                'period' => 'college', 'game_mode' => 'prebuilt', 'team_id' => $team->id,
            ]);
            $this->fail('Exception attendue');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertSame(0, GameSave::where('user_id', $user->id)->count());
        $this->assertSame(0, GamePlayer::count());
    }
}

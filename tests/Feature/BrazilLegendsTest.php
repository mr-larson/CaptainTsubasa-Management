<?php

namespace Tests\Feature;

use App\Models\Player;
use Database\Seeders\PlayerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class BrazilLegendsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_fresh_catalogue_contains_both_brazilian_creators_with_career_portraits(): void
    {
        $this->seed(PlayerSeeder::class);
        $ronaldinho = Player::where('origin', 'world_legends')->where('lastname', 'Ronaldinho')->sole();
        $neymar = Player::where('origin', 'world_legends')->where('lastname', 'Neymar')->sole();
        $ronaldo = Player::where('origin', 'world_legends')->where('nationality', 'Brésil')->where('lastname', 'Ronaldo')->sole();
        foreach ([$ronaldinho, $neymar] as $player) {
            $this->assertSame('Brésil', $player->nationality);
            $this->assertFileExists(public_path('storage/'.$player->photo_path));
            $this->assertLessThan($ronaldo->stats['shot'], $player->stats['shot']);
            $this->assertGreaterThanOrEqual(30, $player->stats['dribble']);
            $this->assertLessThan(20, $player->stats['defense']);
        }
        $this->assertSame('players/ronaldinho-2002.jpg', $ronaldinho->photo_path);
        $this->assertSame('players/neymar-2010.jpg', $neymar->photo_path);
        $this->assertSame('Midfielder', $ronaldinho->position->value);
        $this->assertSame('Forward', $neymar->position->value);
        $this->assertContains('Forward', $ronaldinho->secondary_positions);
        $this->assertContains('Midfielder', $neymar->secondary_positions);
    }

    public function test_addition_is_repeatable_and_preserves_edits_archives_and_saved_players(): void
    {
        $existing = Player::factory()->create(['firstname' => '', 'lastname' => 'Ronaldinho', 'origin' => 'world_legends', 'nationality' => 'Brésil', 'cost' => 999, 'photo_path' => 'players/personal.jpg']);
        $before = $existing->fresh()->getAttributes();
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $existing->id]);
        $savedBefore = $saved->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_18_110000_add_ronaldinho_and_neymar.php');
        $migration->up();
        $neymar = Player::where('origin', 'world_legends')->where('lastname', 'Neymar')->sole();
        $this->assertSame('players/neymar-2010.jpg', $neymar->photo_path);
        $neymar->delete();
        $migration->up();
        $this->assertSame($before, $existing->fresh()->getAttributes());
        $this->assertSoftDeleted('players', ['id' => $neymar->id]);
        $this->assertSame(1, Player::withTrashed()->where('origin', 'world_legends')->where('lastname', 'Neymar')->count());
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
    }
}

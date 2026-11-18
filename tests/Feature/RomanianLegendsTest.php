<?php

namespace Tests\Feature;

use App\Models\Player;
use Database\Seeders\PlayerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class RomanianLegendsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_catalogue_completes_the_eleven_and_keeps_stoica_and_selymes_with_portraits(): void
    {
        $this->seed(PlayerSeeder::class);
        $players = Player::where('origin', 'world_legends')->where('nationality', 'Roumanie')->get();
        $this->assertCount(13, $players);
        $this->assertCount(1, $players->where('position', 'Goalkeeper'));
        $this->assertCount(5, $players->where('position', 'Defender'));
        foreach ($players as $player) {
            $this->assertNotNull($player->photo_path, $player->full_name);
            $this->assertFileExists(public_path('storage/'.$player->photo_path));
        }
        $this->assertSame('players/gheorghe-hagi-1994.jpg', $players->firstWhere('lastname', 'Hagi')->photo_path);
        $this->assertSame('players/alin-stoica-2002.jpg', $players->firstWhere('lastname', 'Stoica')->photo_path);
        $this->assertSame('players/helmuth-duckadam-steaua-vintage.jpg', $players->firstWhere('lastname', 'Duckadam')->photo_path);
        $this->assertSame('players/nicolae-dobrin-1970.jpg', $players->firstWhere('lastname', 'Dobrin')->photo_path);
        $this->assertContains('Midfielder', $players->firstWhere('lastname', 'Popescu')->secondary_positions);
        $this->assertGreaterThan($players->firstWhere('lastname', 'Mutu')->stats['stamina'], $players->firstWhere('lastname', 'Munteanu')->stats['stamina']);
        $this->assertLessThan(Player::where('lastname', 'Hyuga')->sole()->stats['shot'], $players->max(fn ($p) => $p->stats['shot']));
    }

    public function test_addition_preserves_edits_archives_homonyms_and_existing_game_players(): void
    {
        $rows = json_decode(file_get_contents(database_path('data/romanian-legends-2026-11-18.json')), true);
        foreach ($rows as $row) Player::withTrashed()->where('origin', 'world_legends')->where('firstname', $row['firstname'])->where('lastname', $row['lastname'])->forceDelete();
        $identity = ['origin' => 'world_legends', 'nationality' => 'Roumanie'];
        $custom = Player::factory()->create($identity + ['firstname' => 'Gheorghe', 'lastname' => 'Popescu', 'cost' => 999, 'photo_path' => 'players/personal.jpg']);
        $archive = Player::factory()->create($identity + ['firstname' => 'Dorinel', 'lastname' => 'Munteanu']);
        $archive->delete();
        $other = Player::factory()->create(['origin' => 'original', 'firstname' => 'Cristian', 'lastname' => 'Chivu']);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $custom->id]);
        $before = $custom->fresh()->getAttributes();
        $savedBefore = $saved->fresh()->getAttributes();
        $otherBefore = $other->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_18_230000_add_romanian_legends.php');
        $migration->up();
        $migration->up();
        $this->assertSame($before, $custom->fresh()->getAttributes());
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        $this->assertSoftDeleted('players', ['id' => $archive->id]);
        $this->assertSame(10, Player::withTrashed()->where($identity)->whereNotIn('lastname', ['Hagi', 'Selymes', 'Stoica'])->count());
        $this->assertDatabaseHas('players', $identity + ['firstname' => 'Adrian', 'lastname' => 'Mutu', 'photo_path' => 'players/adrian-mutu-2008.webp']);
    }
}

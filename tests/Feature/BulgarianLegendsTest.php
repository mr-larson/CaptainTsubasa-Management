<?php

namespace Tests\Feature;

use App\Models\Player;
use Database\Seeders\PlayerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class BulgarianLegendsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_catalogue_has_the_historical_eleven_plus_berbatov_with_career_portraits(): void
    {
        $this->seed(PlayerSeeder::class);
        $players = Player::where('origin', 'world_legends')->where('nationality', 'Bulgarie')->get();
        // Douze profils d'origine avec portrait, plus Letchkov et Petrov (revue Bulgarie, sans portrait).
        $this->assertCount(14, $players);
        $this->assertCount(1, $players->where('position', 'Goalkeeper'));
        $this->assertCount(4, $players->where('position', 'Defender'));
        foreach ($players->reject(fn ($p) => in_array($p->lastname, ['Letchkov', 'Petrov'], true) && $p->firstname !== 'Stiliyan') as $player) {
            $this->assertNotNull($player->photo_path);
            $this->assertFileExists(public_path('storage/'.$player->photo_path));
        }
        $this->assertSame('players/hristo-stoichkov-1994.jpg', $players->firstWhere('lastname', 'Stoichkov')->photo_path);
        $this->assertSame('players/dimitar-berbatov-2004.webp', $players->firstWhere('lastname', 'Berbatov')->photo_path);
        $this->assertSame('players/dimitar-penev-1970.jpg', $players->firstWhere('lastname', 'Penev')->photo_path);
        $this->assertGreaterThan($players->firstWhere('lastname', 'Petrov')->stats['pass'], $players->firstWhere('lastname', 'Balakov')->stats['pass']);
        $this->assertLessThan(Player::where('lastname', 'Hyuga')->sole()->stats['shot'], $players->max(fn ($p) => $p->stats['shot']));
    }

    public function test_addition_preserves_existing_profiles_archives_homonyms_and_saved_players(): void
    {
        $rows = json_decode(file_get_contents(database_path('data/bulgarian-legends-2026-11-18.json')), true);
        foreach ($rows as $row) Player::withTrashed()->where('origin', 'world_legends')->where('firstname', $row['firstname'])->where('lastname', $row['lastname'])->forceDelete();
        $identity = ['origin' => 'world_legends', 'nationality' => 'Bulgarie'];
        $custom = Player::factory()->create($identity + ['firstname' => 'Krasimir', 'lastname' => 'Balakov', 'cost' => 999, 'photo_path' => 'players/personal.jpg']);
        $archive = Player::factory()->create($identity + ['firstname' => 'Hristo', 'lastname' => 'Bonev']);
        $archive->delete();
        $other = Player::factory()->create(['origin' => 'original', 'firstname' => 'Dimitar', 'lastname' => 'Berbatov']);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $custom->id]);
        $before = $custom->fresh()->getAttributes();
        $savedBefore = $saved->fresh()->getAttributes();
        $otherBefore = $other->fresh()->getAttributes();

        $migration = require database_path('migrations/2026_11_18_220000_add_bulgarian_legends.php');
        $migration->up();
        $migration->up();

        $this->assertSame($before, $custom->fresh()->getAttributes());
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        $this->assertSoftDeleted('players', ['id' => $archive->id]);
        $this->assertSame(13, Player::withTrashed()->where($identity)->where('lastname', '!=', 'Stoichkov')->count());
        $this->assertDatabaseHas('players', $identity + ['firstname' => 'Georgi', 'lastname' => 'Asparuhov', 'photo_path' => 'players/georgi-asparuhov-1970.jpg']);
    }
}

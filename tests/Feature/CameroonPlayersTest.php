<?php

namespace Tests\Feature;

use App\Enums\Nationality;
use App\Helpers\FormationHelper;
use App\Models\Player;
use App\Services\WorldCup\NewWorldCupBuilder;
use Database\Seeders\PlayerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class CameroonPlayersTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_catalogue_adds_a_complete_illustrated_and_playable_cameroon_eleven(): void
    {
        $this->seed(PlayerSeeder::class);
        $players = Player::where('nationality', 'Cameroun')->where('origin', 'world_legends')->get();
        $this->assertCount(11, $players);
        $this->assertCount(1, $players->where('position', 'Goalkeeper'));
        $this->assertCount(4, $players->where('position', 'Defender'));
        $this->assertCount(3, $players->where('position', 'Midfielder'));
        $this->assertCount(3, $players->where('position', 'Forward'));
        foreach ($players as $player) {
            $this->assertNotNull($player->photo_path, $player->full_name);
            $this->assertFileExists(public_path('storage/'.$player->photo_path));
        }
        $this->assertSame('players/samuel-etoo-2002.webp', $players->firstWhere('lastname', "Eto'o")->photo_path);
        $this->assertSame('players/samuel-mbappe-leppe-archive.jpg', $players->firstWhere('lastname', 'Mbappé Léppé')->photo_path);
        $this->assertSame('🇨🇲', Nationality::flag('Cameroun'));
        $this->assertTrue(Nationality::isValid('Cameroun'));
        $this->assertTrue(app(NewWorldCupBuilder::class)->isPlayable('Cameroun'));
        $this->assertArrayHasKey('4-2-1-3', FormationHelper::SLOT_ZONES);
        $this->assertDatabaseHas('nations', ['name' => 'Cameroun', 'code' => 'CM', 'default_formation' => '4-2-1-3', 'flag_path' => 'nations/cm.svg']);
        $this->assertSame($players->max(fn ($p) => $p->stats['attack']), $players->firstWhere('lastname', "Eto'o")->stats['attack']);
        $this->assertLessThan(Player::where('lastname', 'Hyuga')->sole()->stats['shot'], $players->max(fn ($p) => $p->stats['shot']));
    }

    public function test_update_preserves_custom_profiles_archives_homonyms_and_saved_players(): void
    {
        Player::withTrashed()->where('nationality', 'Cameroun')->forceDelete();
        $identity = ['origin' => 'world_legends', 'nationality' => 'Cameroun'];
        $custom = Player::factory()->create($identity + ['firstname' => 'Samuel', 'lastname' => "Eto'o", 'cost' => 999, 'photo_path' => 'players/personal.jpg']);
        $archive = Player::factory()->create($identity + ['firstname' => 'Roger', 'lastname' => 'Milla']);
        $archive->delete();
        $other = Player::factory()->create(['origin' => 'original', 'nationality' => 'Cameroun', 'firstname' => 'Rigobert', 'lastname' => 'Song']);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $custom->id]);
        $before = $custom->fresh()->getAttributes();
        $otherBefore = $other->fresh()->getAttributes();
        $savedBefore = $saved->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_19_000000_add_cameroon_players_and_nation.php');
        $migration->up();
        $migration->up();
        $this->assertSame($before, $custom->fresh()->getAttributes());
        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
        $this->assertSoftDeleted('players', ['id' => $archive->id]);
        $this->assertSame(11, Player::withTrashed()->where($identity)->count());
        $this->assertDatabaseHas('players', $identity + ['firstname' => 'Thomas', 'lastname' => "N'Kono", 'photo_path' => 'players/thomas-nkono-1982.webp']);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\Nationality;
use App\Models\Player;
use Database\Seeders\PlayerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class SenegalPlayersTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_catalogue_separates_eleven_legends_and_two_illustrated_tsubasa_characters(): void
    {
        $this->seed(PlayerSeeder::class);
        $legends = Player::where('nationality', 'Sénégal')->where('origin', 'world_legends')->get();
        $fiction = Player::where('nationality', 'Sénégal')->where('origin', 'captain_tsubasa_international')->get();
        $this->assertCount(11, $legends);
        $this->assertCount(2, $fiction);
        $this->assertCount(1, $legends->where('position', 'Goalkeeper'));
        $this->assertCount(4, $legends->where('position', 'Defender'));
        foreach ($legends->concat($fiction) as $player) {
            $this->assertNotNull($player->photo_path, $player->full_name);
            $this->assertFileExists(public_path('storage/'.$player->photo_path));
        }
        $this->assertSame(['Diallo', 'Senghor'], $fiction->pluck('lastname')->sort()->values()->all());
        $this->assertSame('players/sadio-mane-2022.webp', $legends->firstWhere('lastname', 'Mané')->photo_path);
        $this->assertTrue(app(\App\Services\WorldCup\NewWorldCupBuilder::class)->isPlayable('Sénégal'));
        $this->assertSame('🇸🇳', Nationality::flag('Sénégal'));
        $this->assertTrue(Nationality::isValid('Sénégal'));
        $this->assertDatabaseHas('nations', ['name' => 'Sénégal', 'code' => 'SN', 'flag_path' => 'nations/sn.svg', 'default_formation' => '4-2-1-3']);
        $this->assertGreaterThan($legends->firstWhere('lastname', 'Beye')->stats['defense'], $legends->firstWhere('lastname', 'Koulibaly')->stats['defense']);
        $this->assertLessThan(Player::where('lastname', 'Hyuga')->sole()->stats['shot'], $legends->concat($fiction)->max(fn ($p) => $p->stats['shot']));
    }

    public function test_update_is_idempotent_and_preserves_edits_archives_and_saved_players(): void
    {
        Player::withTrashed()->where('nationality', 'Sénégal')->forceDelete();
        $identity = ['nationality' => 'Sénégal', 'origin' => 'world_legends'];
        $custom = Player::factory()->create($identity + ['firstname' => 'Sadio', 'lastname' => 'Mané', 'cost' => 999, 'photo_path' => 'players/personal.jpg']);
        $archived = Player::factory()->create($identity + ['firstname' => 'Roger', 'lastname' => 'Mendy']);
        $archived->delete();
        $homonym = Player::factory()->create(['firstname' => 'Ismail', 'lastname' => 'Senghor', 'origin' => 'original', 'nationality' => 'Sénégal']);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $custom->id]);
        $before = $custom->fresh()->getAttributes();
        $savedBefore = $saved->fresh()->getAttributes();
        $homonymBefore = $homonym->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_18_240000_add_senegal_players_and_nation.php');
        $migration->up();
        $migration->up();
        $this->assertSame($before, $custom->fresh()->getAttributes());
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
        $this->assertSame($homonymBefore, $homonym->fresh()->getAttributes());
        $this->assertSoftDeleted('players', ['id' => $archived->id]);
        $this->assertSame(11, Player::withTrashed()->where($identity)->count());
        $this->assertDatabaseHas('players', ['origin' => 'captain_tsubasa_international', 'firstname' => 'Ismail', 'lastname' => 'Senghor', 'photo_path' => 'players/ismail-senghor-rise-of-new-champions.png']);
    }
}

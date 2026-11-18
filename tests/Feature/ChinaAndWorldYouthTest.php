<?php

namespace Tests\Feature;

use App\Enums\Nationality;
use App\Enums\PlayerOrigin;
use App\Models\Player;
use Database\Seeders\PlayerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class ChinaAndWorldYouthTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_fresh_catalogue_contains_the_chinese_eleven_and_separate_world_youth_characters_with_portraits(): void
    {
        $this->seed(PlayerSeeder::class);
        $china = Player::where('origin', PlayerOrigin::LEGENDS)->where('nationality', Nationality::CHINE)->get();
        $this->assertCount(11, $china);
        $this->assertCount(1, $china->filter(fn ($p) => $p->position->value === 'Goalkeeper'));
        $this->assertCount(4, $china->filter(fn ($p) => $p->position->value === 'Defender'));
        $this->assertCount(3, $china->filter(fn ($p) => $p->position->value === 'Midfielder'));
        $this->assertCount(3, $china->filter(fn ($p) => $p->position->value === 'Forward'));
        $this->assertTrue(Nationality::isValid('Chine'));
        $this->assertSame('🇨🇳', Nationality::flag('Chine'));
        $this->assertCount(count(Nationality::ALL), array_unique(Nationality::ALL));
        $characters = Player::where('origin', PlayerOrigin::INTERNATIONAL)
            ->whereIn('nationality', ['Chine', 'Corée du Sud', 'Arabie saoudite'])->get();
        $this->assertCount(7, $characters);
        $this->assertCount(3, $characters->where('nationality', 'Chine'));
        $this->assertCount(2, $characters->where('nationality', 'Corée du Sud'));
        $this->assertCount(2, $characters->where('nationality', 'Arabie saoudite'));
        foreach ($china->concat($characters) as $player) {
            $this->assertNotNull($player->photo_path, $player->full_name);
            $this->assertFileExists(public_path('storage/'.$player->photo_path));
        }
        foreach ($characters as $player) $this->assertNotEmpty($player->special_moves, $player->full_name);
        $xiao = $characters->firstWhere('lastname', 'Xiao');
        $fan = $china->firstWhere('lastname', 'Fan');
        $hyuga = Player::where('firstname', 'Kojiro')->where('lastname', 'Hyuga')->sole();
        $this->assertGreaterThan($fan->stats['defense'], $xiao->stats['defense']);
        $this->assertGreaterThanOrEqual(31, $xiao->stats['block']);
        $this->assertLessThan($hyuga->stats['shot'], $xiao->stats['shot']);
        $vulcan = $characters->firstWhere('lastname', 'Vulcan');
        $this->assertContains('Defender', $vulcan->secondary_positions);
        $this->assertGreaterThan($hyuga->stats['heading'], $vulcan->stats['heading']);
        $this->assertLessThan($hyuga->stats['speed'], $vulcan->stats['speed']);
        $wang = $characters->firstWhere('lastname', 'Wang');
        $this->assertSame('cross', $wang->special_moves[0]['base_action']);
        $this->assertGreaterThan($wang->stats['shot'], $wang->stats['cross']);
    }

    public function test_append_is_repeatable_and_preserves_custom_data_archives_other_universes_and_saves(): void
    {
        $rows = json_decode(file_get_contents(database_path('data/china-world-youth-2026-11-18.json')), true);
        foreach ($rows as $row) DB::table('players')->where('origin', $row['origin'])->where('firstname', $row['firstname'])->where('lastname', $row['lastname'])->delete();
        $fan = Player::factory()->create(['firstname' => 'Zhiyi', 'lastname' => 'Fan', 'origin' => PlayerOrigin::LEGENDS, 'nationality' => 'Chine', 'cost' => 999, 'photo_path' => 'players/custom.jpg']);
        $before = $fan->fresh()->getAttributes();
        $xiao = Player::factory()->create(['firstname' => 'Junguang', 'lastname' => 'Xiao', 'origin' => PlayerOrigin::INTERNATIONAL, 'nationality' => 'Chine']);
        $xiao->delete();
        $other = Player::factory()->create(['firstname' => 'Zhiyi', 'lastname' => 'Fan', 'origin' => 'original', 'nationality' => 'Chine']);
        $otherBefore = $other->fresh()->getAttributes();
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $fan->id, 'photo_path' => $fan->photo_path]);
        $savedBefore = $saved->fresh()->getAttributes();
        $countBefore = Player::withTrashed()->count();
        $migration = require database_path('migrations/2026_11_18_140000_add_china_and_world_youth_players.php');
        $migration->up();
        $migration->up();
        $this->assertSame($countBefore + 16, Player::withTrashed()->count());
        $this->assertSame($before, $fan->fresh()->getAttributes());
        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
        $this->assertSoftDeleted('players', ['id' => $xiao->id]);
        $this->assertSame(1, Player::withTrashed()->where('origin', PlayerOrigin::INTERNATIONAL)->where('firstname', 'Junguang')->where('lastname', 'Xiao')->count());
        $this->assertSame('players/mark-owairan-world-youth.png', Player::where('origin', PlayerOrigin::INTERNATIONAL)->where('lastname', 'Owairan')->sole()->photo_path);
    }
}

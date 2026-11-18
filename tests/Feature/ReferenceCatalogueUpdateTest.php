<?php

namespace Tests\Feature;

use App\Models\Player;
use Database\Seeders\PlayerSeeder;
use Database\Seeders\Support\PlayerPhotos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class ReferenceCatalogueUpdateTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_reviewed_update_is_repeatable_and_preserves_photos_archives_and_saved_players(): void
    {
        $snapshot = json_decode(file_get_contents(database_path('data/reference-catalogue-update-2026-11-18.json')), true);
        $update = $snapshot['updates'][0];
        $player = Player::factory()->create(['origin' => $update['origin'], 'firstname' => $update['firstname'], 'lastname' => $update['lastname'], 'photo_path' => 'players/personal.jpg']);
        DB::table('players')->where('id', $player->id)->update($update['previous']);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $player->id, 'photo_path' => $player->photo_path]);
        $savedBefore = $saved->fresh()->getAttributes();
        $removed = Player::factory()->create($snapshot['retirements'][0]);
        $addition = $snapshot['additions'][0];
        $archive = Player::factory()->create(['origin' => $addition['origin'], 'firstname' => $addition['firstname'], 'lastname' => $addition['lastname']]);
        $archive->delete();
        $migration = require database_path('migrations/2026_11_18_070000_update_reference_catalogue_from_reviewed_rosters.php');
        $migration->up();
        $count = DB::table('players')->count();
        $migration->up();
        $this->assertSame($count, DB::table('players')->count());
        foreach ($update['values'] as $field => $value) {
            $actual = DB::table('players')->where('id', $player->id)->value($field);
            if (in_array($field, ['stats', 'secondary_positions', 'special_moves'], true)) {
                $this->assertEquals(json_decode($value ?? 'null', true), json_decode($actual ?? 'null', true));
            } else {
                $this->assertEquals($value, $actual);
            }
        }
        $this->assertSame('players/personal.jpg', $player->fresh()->photo_path);
        $this->assertSoftDeleted('players', ['id' => $removed->id]);
        $this->assertSoftDeleted('players', ['id' => $archive->id]);
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
        $other = $snapshot['additions'][1];
        $this->assertDatabaseHas('players', ['origin' => $other['origin'], 'firstname' => $other['firstname'], 'lastname' => $other['lastname'], 'deleted_at' => null]);
    }

    public function test_later_manual_stat_changes_are_not_overwritten(): void
    {
        $snapshot = json_decode(file_get_contents(database_path('data/reference-catalogue-update-2026-11-18.json')), true);
        $update = $snapshot['updates'][0];
        $player = Player::factory()->create(['origin' => $update['origin'], 'firstname' => $update['firstname'], 'lastname' => $update['lastname']]);
        DB::table('players')->where('id', $player->id)->update($update['previous'] + ['photo_path' => 'players/personal.jpg']);
        DB::table('players')->where('id', $player->id)->update(['cost' => 999]);
        $before = $player->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_18_070000_update_reference_catalogue_from_reviewed_rosters.php');
        $migration->up();
        $this->assertSame($before, $player->fresh()->getAttributes());
    }

    public function test_early_career_portrait_refresh_preserves_custom_photos_and_saved_copies(): void
    {
        $eden = Player::factory()->create(['firstname' => 'Eden', 'lastname' => 'Hazard', 'origin' => 'world_legends', 'photo_path' => 'players/eden-hazard.jpg']);
        $custom = Player::factory()->create(['firstname' => 'Youri', 'lastname' => 'Tielemans', 'origin' => 'world_legends', 'photo_path' => 'players/my-own-tielemans.jpg']);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $eden->id, 'photo_path' => $eden->photo_path]);
        $before = $eden->fresh()->getAttributes();
        $savedBefore = $saved->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_18_071000_refresh_early_belgian_career_portraits.php');
        $migration->up();
        $migration->up();
        $this->assertSame('players/eden-hazard-2010.jpg', $eden->fresh()->photo_path);
        $this->assertSame('players/my-own-tielemans.jpg', $custom->fresh()->photo_path);
        $after = $eden->fresh()->getAttributes();
        unset($before['photo_path'], $after['photo_path']);
        $this->assertEquals($before, $after);
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
    }

    public function test_a_real_player_does_not_inherit_the_portrait_of_an_anime_namesake(): void
    {
        $this->assertSame('players/pepe.webp', PlayerPhotos::pathFor('pepe', 'captain_tsubasa'));
        $row = (new PlayerSeeder())->row('world_legends', ['', 'Pepe', 13, 'Defender', 0, Player::DEFAULT_STATS], ['pepe' => 'Portugal'], []);
        $this->assertSame('players/pepe-portugal-2008.jpg', $row['photo_path']);
        $this->assertSame('Portugal', $row['nationality']);
    }

    public function test_new_portraits_preserve_namesakes_custom_photos_archives_and_saved_players(): void
    {
        $pepe = Player::factory()->create(['firstname' => '', 'lastname' => 'Pepe', 'origin' => 'world_legends', 'nationality' => 'Portugal', 'photo_path' => 'players/pepe.webp']);
        $anime = Player::factory()->create(['firstname' => '', 'lastname' => 'Pepe', 'origin' => 'captain_tsubasa_international', 'nationality' => 'Brésil', 'photo_path' => 'players/pepe.webp']);
        $wrongCountry = Player::factory()->create(['firstname' => '', 'lastname' => 'Pepe', 'origin' => 'world_legends', 'nationality' => 'Brésil', 'photo_path' => null]);
        $custom = Player::factory()->create(['firstname' => 'Ashley', 'lastname' => 'Cole', 'origin' => 'world_legends', 'nationality' => 'Angleterre', 'photo_path' => 'players/my-cole.jpg']);
        $archived = Player::factory()->create(['firstname' => 'Gordon', 'lastname' => 'Banks', 'origin' => 'world_legends', 'nationality' => 'Angleterre', 'photo_path' => null]);
        $archived->delete();
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $pepe->id, 'photo_path' => 'players/pepe.webp']);
        $savedBefore = $saved->fresh()->getAttributes();
        $before = $pepe->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_18_090000_attach_expanded_legend_portraits.php');
        $migration->up();
        $migration->up();
        $this->assertSame('players/pepe-portugal-2008.jpg', $pepe->fresh()->photo_path);
        $this->assertSame('players/pepe.webp', $anime->fresh()->photo_path);
        $this->assertNull($wrongCountry->fresh()->photo_path);
        $this->assertSame('players/my-cole.jpg', $custom->fresh()->photo_path);
        $this->assertNull(Player::withTrashed()->findOrFail($archived->id)->photo_path);
        $this->assertSoftDeleted('players', ['id' => $archived->id]);
        $after = $pepe->fresh()->getAttributes();
        unset($before['photo_path'], $after['photo_path']);
        $this->assertEquals($before, $after);
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
    }

    public function test_fresh_catalogue_contains_all_83_new_portraits_and_separate_pepe_images(): void
    {
        $this->seed(PlayerSeeder::class);
        $portraits = json_decode(file_get_contents(database_path('data/new-legend-portraits-2026-11-18.json')), true);
        $this->assertCount(83, $portraits);
        foreach ($portraits as $portrait) {
            $player = Player::where('origin', 'world_legends')->where('nationality', $portrait['nationality'])
                ->where('firstname', $portrait['firstname'])->where('lastname', $portrait['lastname'])->sole();
            $expectedPath = $portrait['lastname'] === 'Solskjær'
                ? 'players/ole-gunnar-solskjaer-1999.webp' : $portrait['photo_path'];
            $this->assertSame($expectedPath, $player->photo_path);
        }
        $this->assertDatabaseHas('players', ['lastname' => 'Pepe', 'origin' => 'captain_tsubasa_international', 'photo_path' => PlayerPhotos::pathFor('pepe', 'captain_tsubasa_international')]);
        $this->assertDatabaseHas('players', ['lastname' => 'Pepe', 'origin' => 'world_legends', 'nationality' => 'Portugal', 'photo_path' => 'players/pepe-portugal-2008.jpg']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Player;
use Database\Seeders\Support\PlayerPhotos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class LegendVintagePortraitsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_replacement_changes_only_the_old_import_and_preserves_custom_images_archives_and_saves(): void
    {
        $albert = Player::factory()->create(['firstname' => 'Philippe', 'lastname' => 'Albert', 'origin' => 'world_legends', 'photo_path' => 'players/philippe-albert.jpg']);
        $custom = Player::factory()->create(['firstname' => 'Luc', 'lastname' => 'Nilis', 'origin' => 'world_legends', 'photo_path' => 'players/personal.jpg']);
        $archived = Player::factory()->create(['firstname' => 'Michel', 'lastname' => 'Preud\'homme', 'origin' => 'world_legends', 'photo_path' => 'players/michel-preudhomme.jpg']);
        $archived->delete();
        $before = $albert->fresh()->getAttributes();
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $albert->id, 'photo_path' => $albert->photo_path]);
        $savedBefore = $saved->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_17_235000_replace_recent_belgian_portraits.php');
        $migration->up();
        $migration->up();
        $this->assertSame('players/philippe-albert-1994.png', $albert->fresh()->photo_path);
        unset($before['photo_path']);
        $after = $albert->fresh()->getAttributes();
        unset($after['photo_path']);
        $this->assertEquals($before, $after);
        $this->assertSame('players/personal.jpg', $custom->fresh()->photo_path);
        $this->assertSame('players/michel-preudhomme.jpg', Player::withTrashed()->findOrFail($archived->id)->photo_path);
        $this->assertSoftDeleted('players', ['id' => $archived->id]);
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
        $this->assertSame($albert->fresh()->photo_path, PlayerPhotos::pathFor('philippe-albert'));
    }

    public function test_preferred_portraits_have_verified_files_and_explicit_provenance(): void
    {
        $images = json_decode(file_get_contents(resource_path('data/player-illustration-credits.json')), true, 512, JSON_THROW_ON_ERROR);
        $preferred = [];
        foreach ($images as $image) {
            if (!($image['preferred_for_catalog'] ?? false)) continue;
            $identity = ($image['origin'] ?? '').'|'.$image['player_slug'];
            $this->assertNotContains($identity, $preferred, 'Un seul portrait préféré par joueur et par univers.');
            $preferred[] = $identity;
            $file = public_path('storage/'.$image['photo_path']);
            $this->assertSame($image['sha256'], hash_file('sha256', $file));
            $this->assertNotFalse(getimagesize($file));
            $this->assertNotEmpty($image['rights']);
            // Les portraits historiques du projet n'ont pas de source externe
            // documentée (rights le dit explicitement) : seuls le fichier et son
            // empreinte sont vérifiés pour eux.
            $historical = str_starts_with($image['credit'] ?? '', 'Portrait historique du projet');
            if (!$historical) {
                $this->assertNotEmpty($image['source_url']);
            }
            // Personnages de manga sans période de carrière à relire : le champ
            // est absent, mais s'il existe il doit valoir true.
            if (array_key_exists('career_period_reviewed', $image)) {
                $this->assertTrue($image['career_period_reviewed']);
            }
            $this->assertSame($image['photo_path'], PlayerPhotos::pathFor($image['player_slug'], $image['origin'] ?? null));
            if (isset($image['portrait_crop'])) {
                [$x, $y, $width, $height] = $image['portrait_crop'];
                [$originalWidth, $originalHeight] = getimagesize($file);
                $this->assertGreaterThanOrEqual(0, $x);
                $this->assertGreaterThanOrEqual(0, $y);
                $this->assertGreaterThan(0, $width);
                $this->assertGreaterThan(0, $height);
                $this->assertLessThanOrEqual($originalWidth, $x + $width);
                $this->assertLessThanOrEqual($originalHeight, $y + $height);
            }
        }
    }

    public function test_full_portrait_update_preserves_manual_images_player_data_and_saved_games(): void
    {
        $player = Player::factory()->create(['firstname' => 'Mbo', 'lastname' => 'Mpenza', 'origin' => 'world_legends', 'photo_path' => null]);
        $custom = Player::factory()->create(['firstname' => 'Kevin', 'lastname' => 'De Bruyne', 'origin' => 'world_legends', 'photo_path' => 'players/my-personal-photo.jpg']);
        $oldImport = Player::factory()->create(['firstname' => 'Luc', 'lastname' => 'Nilis', 'origin' => 'world_legends', 'photo_path' => 'players/luc-nilis-1998.jpg']);
        $archive = Player::factory()->create(['firstname' => 'Thibaut', 'lastname' => 'Courtois', 'origin' => 'world_legends', 'photo_path' => null]);
        $archive->delete();
        $otherOrigin = Player::factory()->create(['firstname' => 'Mbo', 'lastname' => 'Mpenza', 'origin' => 'original', 'photo_path' => null]);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $player->id, 'photo_path' => null]);
        $savedBefore = $saved->fresh()->getAttributes();
        $before = $player->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_18_010000_attach_reviewed_legend_portraits.php');
        $migration->up();
        $migration->up();
        $this->assertSame(PlayerPhotos::pathFor('mbo-mpenza'), $player->fresh()->photo_path);
        $this->assertNotNull($player->fresh()->photo_path);
        $this->assertSame('players/luc-nilis-1991.jpg', $oldImport->fresh()->photo_path);
        $this->assertSame('players/my-personal-photo.jpg', $custom->fresh()->photo_path);
        $this->assertNull(Player::withTrashed()->findOrFail($archive->id)->photo_path);
        $this->assertSoftDeleted('players', ['id' => $archive->id]);
        $this->assertNull($otherOrigin->fresh()->photo_path);
        $after = $player->fresh()->getAttributes();
        unset($before['photo_path'], $after['photo_path']);
        $this->assertEquals($before, $after);
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
    }
}

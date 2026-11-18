<?php

namespace Tests\Feature;

use App\Enums\PlayerOrigin;
use App\Models\Player;
use App\Support\PlayerPhotoCredits;
use Database\Seeders\PlayerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlayerPhotoCreditsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_imported_photo_has_a_local_file_and_complete_free_license_credits(): void
    {
        $photos = PlayerPhotoCredits::all();
        $this->assertNotEmpty($photos);
        $this->assertSame(count($photos), count(array_unique(array_column($photos, 'slug'))));
        foreach ($photos as $photo) {
            $file = public_path('storage/' . $photo['photo_path']);
            $this->assertFileExists($file);
            $this->assertSame($photo['sha256'], hash_file('sha256', $file));
            $this->assertNotFalse(getimagesize($file));
            $this->assertNotEmpty($photo['author']);
            $this->assertMatchesRegularExpression('/^(CC BY(?:-SA)? [234]\.0|CC0|Public domain)$/', $photo['license']);
            $this->assertSame('commons.wikimedia.org', parse_url($photo['source_url'], PHP_URL_HOST));
            $this->assertSame('https', parse_url($photo['license_url'], PHP_URL_SCHEME));
            $this->assertSame(strip_tags($photo['author']), $photo['author']);
        }
    }

    public function test_import_fills_missing_portraits_without_overwriting_manual_photos_or_stats(): void
    {
        $photo = PlayerPhotoCredits::all()[0];
        $player = Player::factory()->create([
            'firstname' => $photo['firstname'], 'lastname' => $photo['lastname'],
            'origin' => PlayerOrigin::LEGENDS, 'nationality' => 'Belgique', 'photo_path' => null,
        ]);
        $before = $player->stats;
        PlayerPhotoCredits::attachMissing();
        $this->assertSame($photo['photo_path'], $player->fresh()->photo_path);
        $this->assertSame($before, $player->fresh()->stats);
        $player->update(['photo_path' => 'players/custom.jpg']);
        PlayerPhotoCredits::attachMissing();
        $this->assertSame('players/custom.jpg', $player->fresh()->photo_path);
    }

    public function test_fresh_seed_uses_the_same_portraits_and_credits_are_public(): void
    {
        $this->seed(PlayerSeeder::class);
        foreach (PlayerPhotoCredits::all() as $photo) {
            $player = Player::where('origin', PlayerOrigin::LEGENDS)
                ->where('firstname', $photo['firstname'])->where('lastname', $photo['lastname'])->first();
            // Les crédits et fichiers des joueurs retirés restent utiles aux anciennes parties.
            if ($player) $this->assertSame(\Database\Seeders\Support\PlayerPhotos::pathFor($photo['slug']), $player->photo_path);
        }
        $this->get(route('photo-credits'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('PhotoCredits')->has('photos', count(PlayerPhotoCredits::all())));
    }
}

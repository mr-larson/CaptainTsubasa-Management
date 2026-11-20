<?php

namespace Tests\Feature;

use App\Enums\Nationality;
use App\Enums\PlayerOrigin;
use App\Models\Player;
use Database\Seeders\PlayerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class CuratedTsubasaInternationalsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_fresh_catalogue_keeps_47_illustrated_characters_plus_three_new_without_portrait_and_omits_archived_secondary_roles(): void
    {
        $this->seed(PlayerSeeder::class);
        $players = Player::where('origin', PlayerOrigin::INTERNATIONAL)->get();
        // 47 personnages illustrés, plus 22 ajoutés par la revue des internationaux (sans portrait), puis 4 Américains et 10 portraits officiels Bandai ajoutés.
        $this->assertCount(73, $players);
        $withoutPortrait = ['Luke', 'Juanito', 'Raphael', 'Haas', 'Christiansen', 'Schmidt', 'Goethe', 'Bossis', 'Amoros', 'Lensenblink', 'Ramirez', 'Branco'];
        foreach ($players->reject(fn ($p) => in_array($p->lastname, $withoutPortrait, true)) as $player) {
            $this->assertNotNull($player->photo_path, $player->full_name);
            $this->assertFileExists(public_path('storage/'.$player->photo_path));
        }
        foreach (json_decode(file_get_contents(database_path('data/secondary-internationals-retirement-2026-11-18.json')), true) as $identity) {
            // Les cinq guerriers aztèques sont réintégrés par la demande ultérieure.
            if ($identity['nationality'] === 'Mexique' && in_array($identity['lastname'], ['Zaragoza', 'Garcia', 'Suarez', 'Alvez', 'Lopez'], true)) continue;
            $this->assertDatabaseMissing('players', $identity);
        }
        foreach (json_decode(file_get_contents(database_path('data/key-international-portraits-2026-11-18.json')), true) as $portrait) {
            $this->assertDatabaseHas('players', ['firstname' => $portrait['firstname'], 'lastname' => $portrait['lastname'], 'origin' => PlayerOrigin::INTERNATIONAL, 'photo_path' => \Database\Seeders\Support\PlayerPhotos::pathFor(\Illuminate\Support\Str::slug($portrait['firstname'].' '.$portrait['lastname']), PlayerOrigin::INTERNATIONAL)]);
        }
        $zangiev = $players->firstWhere('lastname', 'Zangiev');
        $this->assertSame('Ouzbékistan', $zangiev->nationality);
        $this->assertSame('🇺🇿', Nationality::flag($zangiev->nationality));
        $this->assertTrue(Nationality::isValid($zangiev->nationality));
        $this->assertSame('Forward', $zangiev->position->value);
        $this->assertSame('zangiev_great_rock_blaster', $zangiev->special_moves[0]['key']);
        $hyuga = Player::where('firstname', 'Kojiro')->where('lastname', 'Hyuga')->sole();
        $this->assertLessThan($hyuga->stats['shot'], $zangiev->stats['shot']);
    }

    public function test_curation_preserves_custom_portraits_homonyms_stats_and_saved_players(): void
    {
        $secondary = Player::factory()->create(['firstname' => '', 'lastname' => 'Blanco', 'origin' => PlayerOrigin::INTERNATIONAL, 'nationality' => 'Brésil', 'photo_path' => null]);
        $before = $secondary->fresh()->getAttributes();
        $custom = Player::factory()->create(['firstname' => '', 'lastname' => 'Totti', 'origin' => PlayerOrigin::INTERNATIONAL, 'nationality' => 'Italie', 'photo_path' => 'players/personal.jpg']);
        $customBefore = $custom->fresh()->getAttributes();
        $homonym = Player::factory()->create(['firstname' => '', 'lastname' => 'Blanco', 'origin' => PlayerOrigin::LEGENDS, 'nationality' => 'Brésil', 'photo_path' => null]);
        $homonymBefore = $homonym->fresh()->getAttributes();
        $hernandez = Player::factory()->create(['firstname' => 'Gino', 'lastname' => 'Hernandez', 'origin' => PlayerOrigin::INTERNATIONAL, 'nationality' => 'Italie', 'photo_path' => null]);
        $hernandezBefore = $hernandez->fresh()->getAttributes();
        $gentile = Player::factory()->create(['firstname' => 'Salvatore', 'lastname' => 'Gentile', 'origin' => PlayerOrigin::INTERNATIONAL, 'nationality' => 'Italie', 'photo_path' => 'players/custom-gentile.jpg']);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $secondary->id, 'photo_path' => null]);
        $savedBefore = $saved->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_18_150000_curate_tsubasa_internationals_and_attach_portraits.php');
        $migration->up();
        $migration->up();
        $this->assertSoftDeleted('players', ['id' => $secondary->id]);
        $after = Player::withTrashed()->findOrFail($secondary->id)->getAttributes();
        unset($before['deleted_at'], $before['updated_at'], $after['deleted_at'], $after['updated_at']);
        $this->assertSame($before, $after);
        $this->assertSame($customBefore, $custom->fresh()->getAttributes());
        $this->assertSame($homonymBefore, $homonym->fresh()->getAttributes());
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
        $this->assertSame('players/gino-hernandez-official.png', $hernandez->fresh()->photo_path);
        $after = $hernandez->fresh()->getAttributes();
        unset($hernandezBefore['photo_path'], $after['photo_path']);
        $this->assertSame($hernandezBefore, $after);
        $this->assertSame('players/custom-gentile.jpg', $gentile->fresh()->photo_path);
    }

    public function test_zangiev_append_is_idempotent_and_does_not_reactivate_a_personal_archive(): void
    {
        $player = Player::factory()->create(['firstname' => '', 'lastname' => 'Zangiev', 'origin' => PlayerOrigin::INTERNATIONAL, 'nationality' => 'Ouzbékistan', 'cost' => 999, 'photo_path' => 'players/personal.jpg']);
        $before = $player->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_18_151000_add_zangiev.php');
        $migration->up();
        $this->assertSame($before, $player->fresh()->getAttributes());
        $player->delete();
        $migration->up();
        $this->assertSoftDeleted('players', ['id' => $player->id]);
    }
    public function test_bandai_refresh_preserves_manual_portraits_archives_stats_and_saved_players(): void
    {
        $player = Player::factory()->create(['firstname' => 'Karl-Heinz', 'lastname' => 'Schneider', 'origin' => PlayerOrigin::INTERNATIONAL, 'nationality' => 'Allemagne', 'photo_path' => 'players/karl-heinz-schneider.webp']);
        $before = $player->fresh()->getAttributes();
        $custom = Player::factory()->create(['firstname' => 'Juan', 'lastname' => 'Diaz', 'origin' => PlayerOrigin::INTERNATIONAL, 'nationality' => 'Argentine', 'photo_path' => 'players/personal.jpg']);
        $archive = Player::factory()->create(['firstname' => 'Louis', 'lastname' => 'Napoleon', 'origin' => PlayerOrigin::INTERNATIONAL, 'nationality' => 'France', 'photo_path' => null]);
        $archive->delete();
        $other = Player::factory()->create(['firstname' => 'Karl-Heinz', 'lastname' => 'Schneider', 'origin' => PlayerOrigin::LEGENDS, 'nationality' => 'Allemagne', 'photo_path' => 'players/karl-heinz-schneider.webp']);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $player->id, 'photo_path' => $player->photo_path]);
        $savedBefore = $saved->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_18_160000_refresh_bandai_international_portraits.php');
        $migration->up();
        $migration->up();
        $this->assertSame('players/karl-heinz-schneider-world-fighters.png', $player->fresh()->photo_path);
        $after = $player->fresh()->getAttributes();
        unset($before['photo_path'], $after['photo_path']);
        $this->assertSame($before, $after);
        $this->assertSame('players/personal.jpg', $custom->fresh()->photo_path);
        $this->assertNull(Player::withTrashed()->findOrFail($archive->id)->photo_path);
        $this->assertSame('players/karl-heinz-schneider.webp', $other->fresh()->photo_path);
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
    }

    public function test_transparent_refresh_updates_only_known_black_backgrounds_in_catalogue_and_saves(): void
    {
        $identity = ['firstname' => 'Yong-un', 'lastname' => 'Lee', 'origin' => PlayerOrigin::INTERNATIONAL, 'nationality' => Nationality::COREE_DU_SUD];
        $old = 'players/yong-un-lee-world-fighters.png';
        $new = 'players/yong-un-lee-world-youth.png';
        $player = Player::factory()->create($identity + ['photo_path' => $old]);
        $saved = $this->makePlayer($this->makeSave(), $identity + ['base_player_id' => $player->id, 'photo_path' => $old]);
        $custom = Player::factory()->create($identity + ['photo_path' => 'players/personal.png']);
        $before = $player->fresh()->getAttributes();
        $savedBefore = $saved->fresh()->getAttributes();

        $migration = require database_path('migrations/2026_11_18_170000_use_transparent_international_portraits.php');
        $migration->up();
        $migration->up();

        $this->assertSame($new, $player->fresh()->photo_path);
        $this->assertSame($new, $saved->fresh()->photo_path);
        $this->assertSame('players/personal.png', $custom->fresh()->photo_path);
        $after = $player->fresh()->getAttributes();
        $savedAfter = $saved->fresh()->getAttributes();
        unset($before['photo_path'], $after['photo_path'], $savedBefore['photo_path'], $savedAfter['photo_path']);
        $this->assertSame($before, $after);
        $this->assertSame($savedBefore, $savedAfter);
    }

    public function test_mexico_restoration_preserves_ids_edits_manual_archives_and_saved_players(): void
    {
        $identity = ['origin' => PlayerOrigin::INTERNATIONAL, 'nationality' => Nationality::MEXIQUE, 'firstname' => ''];
        // Reconstituer la base avant cette migration, qui est aussi exécutée au démarrage du test.
        Player::withTrashed()->where($identity)->whereIn('lastname', ['Zaragoza', 'Garcia', 'Suarez', 'Alvez', 'Lopez'])->forceDelete();
        $archived = Player::factory()->create($identity + ['lastname' => 'Garcia', 'photo_path' => null, 'cost' => 999]);
        $archived->delete();
        $before = Player::withTrashed()->findOrFail($archived->id)->getAttributes();
        $custom = Player::factory()->create($identity + ['lastname' => 'Suarez', 'photo_path' => 'players/personal.png']);
        $manualArchive = Player::factory()->create($identity + ['lastname' => 'Lopez', 'photo_path' => 'players/manual.png']);
        $manualArchive->delete();
        $homonym = Player::factory()->create(['origin' => PlayerOrigin::LEGENDS, 'nationality' => Nationality::MEXIQUE, 'firstname' => '', 'lastname' => 'Garcia', 'photo_path' => null]);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $archived->id, 'photo_path' => null]);
        $savedBefore = $saved->fresh()->getAttributes();

        $migration = require database_path('migrations/2026_11_18_210000_restore_mexico_aztec_warriors.php');
        $migration->up();
        $migration->up();

        $restored = Player::findOrFail($archived->id);
        $this->assertSame('players/garcia-world-fighters.png', $restored->photo_path);
        $after = $restored->getAttributes();
        unset($before['deleted_at'], $before['updated_at'], $before['photo_path'], $after['deleted_at'], $after['updated_at'], $after['photo_path']);
        $this->assertSame($before, $after);
        $this->assertSame('players/personal.png', $custom->fresh()->photo_path);
        $this->assertSoftDeleted('players', ['id' => $manualArchive->id]);
        $this->assertNull($homonym->fresh()->photo_path);
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
        $this->assertSame(5, Player::withTrashed()->where($identity)->count());
        $this->assertDatabaseHas('players', $identity + ['lastname' => 'Alvez', 'photo_path' => 'players/alvez-world-fighters.png', 'deleted_at' => null]);
    }

}

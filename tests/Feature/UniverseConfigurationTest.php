<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\GameSaves\GamePlayer;
use App\Support\GameConfig;
use App\Support\UniverseOptions;
use Database\Seeders\Players\Original\Roster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class UniverseConfigurationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_cleanup_preserves_illustrated_personal_players_and_existing_saves(): void
    {
        $unillustrated = Player::factory()->create(['origin' => 'original', 'firstname' => 'Hiroki', 'lastname' => 'Matsunaga', 'photo_path' => null]);
        $illustrated = Player::factory()->create(['origin' => 'original', 'firstname' => 'Daiki', 'lastname' => 'Kuroiwa', 'photo_path' => 'players/manual.jpg']);
        $personal = Player::factory()->create(['origin' => 'original', 'firstname' => 'Gauthier', 'lastname' => 'Leblanc', 'photo_path' => null]);
        $other = Player::factory()->create(['origin' => 'ao_ashi', 'firstname' => 'Hiroki', 'lastname' => 'Matsunaga', 'photo_path' => null]);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $unillustrated->id]);
        $before = $saved->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_17_210000_archive_unillustrated_original_players.php');
        $migration->up(); $migration->up();
        $this->assertSoftDeleted('players', ['id' => $unillustrated->id]);
        foreach ([$illustrated, $personal, $other] as $player) $this->assertFalse($player->fresh()->trashed());
        $this->assertSame($before, $saved->fresh()->getAttributes());
        $names = collect(Roster::players())->map(fn ($p) => $p[0].' '.$p[1]);
        $this->assertCount(26, $names);
        $this->assertContains('Nico Perera', $names);
        $this->assertContains('Gauthier Leblanc', $names);
        $this->assertNotContains('Hiroki Matsunaga', $names);
    }

    public function test_country_selection_filters_only_legends_and_keeps_legacy_saves_compatible(): void
    {
        $save = $this->makeSave();
        $french = $this->makePlayer($save, ['origin' => 'world_legends', 'nationality' => 'France']);
        $this->makePlayer($save, ['origin' => 'world_legends', 'nationality' => 'Belgique']);
        $anime = $this->makePlayer($save, ['origin' => 'captain_tsubasa_international', 'nationality' => 'Belgique']);
        $unknown = $this->makePlayer($save, ['origin' => null]);
        $save->setConfig(['visible_origins' => ['world_legends' => true], 'internationals_visible' => true]);
        $this->assertSame(4, GamePlayer::where('game_save_id', $save->id)->visibleForConfig($save)->count());
        $save->setConfig(['visible_origins' => ['world_legends' => true], 'internationals_visible' => true, 'legends_countries' => ['France']]);
        $this->assertEqualsCanonicalizing([$french->id, $anime->id, $unknown->id], GamePlayer::where('game_save_id', $save->id)->visibleForConfig($save)->pluck('id')->all());
        $save->setConfig(['legends_countries' => []]);
        $this->assertEqualsCanonicalizing([$anime->id, $unknown->id], GamePlayer::where('game_save_id', $save->id)->visibleForConfig($save)->pluck('id')->all());
        $this->assertSame(4, $save->gamePlayers()->count());
    }

    public function test_options_count_active_catalogue_and_saved_rosters_separately(): void
    {
        // Isoler les profils de ce scénario des données ajoutées par les migrations du catalogue.
        Player::query()->delete();
        Player::factory()->create(['origin' => 'world_legends', 'nationality' => 'France']);
        Player::factory()->create(['origin' => 'world_legends', 'nationality' => 'France'])->delete();
        Player::factory()->create(['origin' => 'captain_tsubasa', 'nationality' => 'Allemagne']);
        $options = UniverseOptions::forPlayers();
        $this->assertSame(1, $options['counts']['world_legends']);
        $this->assertSame(1, $options['counts']['captain_tsubasa_international']);
        $this->assertSame([['name' => 'France', 'flag' => '🇫🇷', 'count' => 1]], $options['countries']);
        $save = $this->makeSave();
        $this->makePlayer($save, ['origin' => 'world_legends', 'nationality' => 'Belgique']);
        $this->assertSame('Belgique', UniverseOptions::forPlayers(GamePlayer::where('game_save_id', $save->id))['countries'][0]['name']);
        $this->assertSame(['France'], GameConfig::sanitize(['legends_countries' => ['France', 'France', 'Atlantide']])['legends_countries']);
        $this->assertNull(GameConfig::sanitize(['legends_countries' => null])['legends_countries']);
    }
}

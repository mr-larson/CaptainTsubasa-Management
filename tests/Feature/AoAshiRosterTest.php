<?php

namespace Tests\Feature;

use App\Models\Player;
use Database\Seeders\PlayerSeeder;
use Database\Seeders\Players\AoAshi\Roster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class AoAshiRosterTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_fresh_catalog_includes_a_balanced_nakamura_with_his_illustration(): void
    {
        $this->seed(PlayerSeeder::class);

        $taira = Player::where('origin', 'ao_ashi')->where('firstname', 'Taira')->where('lastname', 'Nakamura')->sole();
        $this->assertSame(9, Player::where('origin', 'ao_ashi')->count());
        $this->assertSame('Japon', $taira->nationality);
        $this->assertSame('Midfielder', $taira->position->value);
        $this->assertContains('Defender', $taira->secondary_positions);
        $this->assertGreaterThan($taira->stats['attack'], $taira->stats['defense']);
        $finisher = Player::where('origin', 'ao_ashi')->where('firstname', 'Soichiro')->sole();
        $this->assertLessThan($finisher->stats['shot'], $taira->stats['shot']);
        $this->assertSame('pass', $taira->special_moves[0]['base_action']);
        $this->assertSame('players/taira-nakamura.png', $taira->photo_path);

        $credits = json_decode(file_get_contents(resource_path('data/player-illustration-credits.json')), true, 512, JSON_THROW_ON_ERROR);
        $credit = collect($credits)->firstWhere('slug', 'taira-nakamura');
        $this->assertNotNull($credit);
        $file = public_path('storage/'.$taira->photo_path);
        $this->assertSame($credit['sha256'], hash_file('sha256', $file));
        $this->assertNotFalse(getimagesize($file));
        $this->assertSame('aoashi-pr.com', parse_url($credit['source_url'], PHP_URL_HOST));
        $this->assertSame('center top', $credit['object_position']);
    }

    public function test_migration_only_adds_the_missing_reference_and_preserves_edits_archives_and_saved_players(): void
    {
        $seeder = new PlayerSeeder();
        $moves = Roster::specialMoves();
        foreach (Roster::players() as $profile) {
            if ($profile[0] !== 'Taira' || $profile[1] !== 'Nakamura') {
                DB::table('players')->insert($seeder->row('ao_ashi', $profile, [], $moves));
            }
        }
        $reference = Player::where('firstname', 'Eisaku')->sole();
        $reference->update(['cost' => 123, 'photo_path' => 'players/manual.png']);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $reference->id, 'origin' => 'ao_ashi', 'shot' => 37]);
        $before = DB::table('players')->orderBy('id')->get()->keyBy('id');
        $savedBefore = $saved->fresh()->getAttributes();

        $migration = require database_path('migrations/2026_11_17_230000_add_taira_nakamura.php');
        $migration->up();
        $this->assertSame(9, Player::where('origin', 'ao_ashi')->count());
        foreach ($before as $id => $row) {
            $this->assertEquals($row, DB::table('players')->find($id));
        }
        // La revue du 20 novembre met Nakamura à jour : son profil final est comparé au catalogue actuel.
        (require database_path('migrations/2026_11_20_230000_review_ao_ashi_players.php'))->up();
        $taira = Player::where('firstname', 'Taira')->sole();
        $profile = collect(Roster::players())->first(fn ($p) => $p[0] === 'Taira' && $p[1] === 'Nakamura');
        $expected = $seeder->row('ao_ashi', $profile, [], $moves);
        foreach ($expected as $key => $value) {
            $actual = $taira->getRawOriginal($key);
            if (in_array($key, ['stats', 'secondary_positions', 'special_moves'], true)) {
                // MySQL normalise l'ordre des clés et l'espacement des colonnes JSON.
                $this->assertEquals(json_decode($value, true), json_decode($actual, true), $key);
            } else {
                $this->assertEquals($value, $actual, $key);
            }
        }

        $taira->update(['cost' => 17, 'photo_path' => 'players/personal.png', 'stats' => array_replace($taira->stats, ['shot' => 31])]);
        $edited = $taira->fresh()->getAttributes();
        $migration->up();
        $this->assertSame($edited, $taira->fresh()->getAttributes());
        $taira->delete();
        $migration->up();
        $migration->down();
        $this->assertSame(9, Player::withTrashed()->where('origin', 'ao_ashi')->count());
        $this->assertSoftDeleted('players', ['id' => $taira->id]);
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
    }
}

<?php

namespace Tests\Feature;

use App\Enums\Nationality;
use App\Models\Player;
use Database\Seeders\PlayerSeeder;
use Database\Seeders\Players\InternationalLegends\Roster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class HistoricalRosterImportTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_four_lists_are_reconciled_and_every_new_profile_is_seeded_once(): void
    {
        $audit = json_decode(file_get_contents(resource_path('data/historical-roster-import.json')), true);
        $this->assertCount(200, $audit);
        $this->assertCount(4, array_unique(array_column($audit, 'source')));
        $this->assertCount(124, array_filter($audit, fn ($r) => $r['status'] === 'à ajouter'));
        $this->seed(PlayerSeeder::class);
        $players = Player::where('origin', 'world_legends')->get();
        $this->assertCount(449, $players);
        $this->assertCount(449, $players->unique(fn ($p) => Str::slug($p->full_name)));
        foreach ($audit as $row) {
            if ($row['status'] === 'archivé') {
                $this->assertNull($players->first(fn ($p) => $p->firstname === $row['firstname'] && $p->lastname === $row['lastname']));
                continue;
            }
            $player = $players->first(fn ($p) => $p->firstname === $row['firstname'] && $p->lastname === $row['lastname']);
            $this->assertNotNull($player, $row['source_name']);
            $this->assertSame($row['nationality'], $player->nationality);
            $this->assertTrue(Nationality::isValid($player->nationality));
            foreach ($player->stats as $value) {
                $this->assertGreaterThanOrEqual(0, $value);
                $this->assertLessThanOrEqual(100, $value);
            }
        }
    }

    public function test_migration_preserves_existing_edits_archives_and_saved_copies(): void
    {
        $existing = Player::factory()->create(['firstname' => 'Oliver', 'lastname' => 'Kahn', 'origin' => 'world_legends', 'cost' => 321]);
        $archived = Player::factory()->create(['firstname' => 'Axel', 'lastname' => 'Witsel', 'origin' => 'world_legends', 'nationality' => 'Belgique']);
        $archived->delete();
        $before = $existing->fresh()->getAttributes();
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $existing->id]);
        $savedBefore = $saved->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_17_220000_add_historical_roster_lists.php');
        $migration->up();
        $count = DB::table('players')->count();
        $migration->up();
        $this->assertSame($count, DB::table('players')->count());
        $this->assertSame($before, $existing->fresh()->getAttributes());
        $this->assertSoftDeleted('players', ['id' => $archived->id]);
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
    }
}

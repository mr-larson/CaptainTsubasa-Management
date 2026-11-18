<?php

namespace Tests\Feature;

use App\Enums\Nationality;
use App\Enums\PlayerOrigin;
use App\Models\Player;
use Database\Seeders\PlayerSeeder;
use Database\Seeders\Players\InternationalLegends\Countries\Belgium;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Concerns\BuildsGameWorld;

class BelgianLegendsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_catalog_respects_the_spreadsheet_order_and_secondary_positions(): void
    {
        $source = fopen(base_path('tests/Fixtures/belgian_legends.csv'), 'r');
        fgetcsv($source);
        $players = Belgium::players();
        $groups = ['GB' => 'Goalkeeper', 'DC' => 'Defender', 'DD' => 'Defender', 'DG' => 'Defender',
            'MDC' => 'Midfielder', 'MC' => 'Midfielder', 'MOC' => 'Midfielder', 'MD' => 'Midfielder', 'MG' => 'Midfielder',
            'AD' => 'Forward', 'AG' => 'Forward', 'AT' => 'Forward', 'BU' => 'Forward'];
        $index = 0;
        while (($row = fgetcsv($source)) !== false) {
            [$rank, $first, $last, $position, $secondary] = $row;
            if (!collect($players)->contains(fn ($p) => $p[0] === $first && $p[1] === $last)) continue;
            $player = $players[$index++];
            $this->assertSame([$first, $last], array_slice($player, 0, 2));
            $this->assertSame($groups[$position], $player[3]);
            $expected = $secondary && $groups[$secondary] !== $groups[$position] ? [$groups[$secondary]] : [];
            $this->assertSame($expected, $player[7]);
            foreach ($player[5] as $stat) {
                $this->assertGreaterThanOrEqual(0, $stat);
                $this->assertLessThanOrEqual(100, $stat);
            }
        }
        fclose($source);
        $this->assertSame(41, $index);
    }

    public function test_seeding_produces_a_playable_belgium_with_keepers_and_distinct_tiers(): void
    {
        $this->seed(PlayerSeeder::class);
        $belgians = Player::where('nationality', Nationality::BELGIQUE)->get();
        $this->assertCount(78, $belgians);
        $this->assertCount(10, $belgians->where('position', 'Goalkeeper'));
        $this->assertSame([PlayerOrigin::LEGENDS], $belgians->pluck('origin')->unique()->values()->all());
        $this->assertSame('🇧🇪', Nationality::flag(Nationality::BELGIQUE));
        $this->assertSame(30, Player::where('lastname', 'De Bruyne')->firstOrFail()->stats['pass']);
        $this->assertSame(30, Player::where('firstname', 'Eden')->where('lastname', 'Hazard')->firstOrFail()->stats['dribble']);
        $this->assertSame(30, Player::where('lastname', 'Courtois')->firstOrFail()->stats['hand_save']);
        $this->assertGreaterThan(Player::where('lastname', 'Trossard')->firstOrFail()->stats['attack'],
            Player::where('lastname', 'Lukaku')->firstOrFail()->stats['attack']);
        $this->assertGreaterThan(Player::where('lastname', 'Deschacht')->firstOrFail()->stats['defense'],
            Player::where('lastname', 'Kompany')->firstOrFail()->stats['defense']);
    }

    public function test_removed_players_are_retired_without_changing_saved_copies_or_other_origins(): void
    {
        $this->seed(PlayerSeeder::class);
        $removed = Player::factory()->create(['firstname' => 'Dries', 'lastname' => 'Mertens',
            'origin' => PlayerOrigin::LEGENDS, 'nationality' => 'Belgique']);
        $other = Player::factory()->create(['firstname' => 'Dries', 'lastname' => 'Mertens',
            'origin' => 'original', 'nationality' => 'Belgique']);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $removed->id, 'shot' => 42]);
        $before = $saved->fresh()->getAttributes();
        $remaining = Player::where('lastname', 'Doku')->firstOrFail();
        $stats = $remaining->stats;
        $migration = require database_path('migrations/2026_11_17_190000_retire_removed_belgian_legends.php');
        $migration->up();
        $migration->up();
        $this->assertSoftDeleted('players', ['id' => $removed->id]);
        $this->assertFalse($other->fresh()->trashed());
        $this->assertSame($before, $saved->fresh()->getAttributes());
        $this->assertSame($stats, $remaining->fresh()->stats);
        $this->assertSame(78, Player::where('origin', PlayerOrigin::LEGENDS)->where('nationality', 'Belgique')->count());
    }

    public function test_removing_players_does_not_promote_the_remaining_players(): void
    {
        $players = collect(Belgium::players())->keyBy(fn ($p) => $p[0].' '.$p[1]);
        $this->assertStringContainsString('au-dessus de la moyenne', $players['François Van der Elst'][6]);
        $this->assertStringContainsString('moyen.', $players['Youri Tielemans'][6]);
        $this->assertStringContainsString('en dessous de la moyenne', $players['Jérémy Doku'][6]);
        $this->assertStringContainsString('en dessous de la moyenne', $players['Bart Goor'][6]);
    }

    public function test_migration_adds_only_missing_players_without_overwriting_edits(): void
    {
        Player::factory()->create();
        $migration = require database_path('migrations/2026_11_17_170000_add_belgian_legends.php');
        $migration->up();
        $player = Player::where('lastname', 'De Bruyne')->firstOrFail();
        $player->update(['cost' => 321]);
        $migration->up();
        $this->assertSame(41, Player::where('nationality', Nationality::BELGIQUE)->count());
        $this->assertSame(321, $player->fresh()->cost);
    }
}

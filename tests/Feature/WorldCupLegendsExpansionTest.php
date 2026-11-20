<?php

namespace Tests\Feature;

use App\Enums\Nationality;
use App\Enums\PlayerOrigin;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\PlayerSeeder;
use Database\Seeders\Players\InternationalLegends\Countries\Belgium;
use Database\Seeders\Players\InternationalLegends\Roster;
use Database\Seeders\Players\InternationalLegends\Support\WorldLegendProfiles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class WorldCupLegendsExpansionTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_all_44_source_players_are_present_without_duplicates_and_with_valid_nationalities(): void
    {
        $this->seed(PlayerSeeder::class);
        $players = Player::where('origin', PlayerOrigin::LEGENDS)->get();
        $this->assertCount(591, $players);
        $this->assertCount(591, $players->unique(fn ($p) => Str::slug($p->full_name)));
        $source = fopen(base_path('tests/Fixtures/world_cup_legends.csv'), 'r');
        fgetcsv($source);
        $count = 0;
        while (($row = fgetcsv($source)) !== false) {
            [$rank, $first, $last, $nation, $position, $secondary, $status, $canonicalName] = $row;
            $player = $players->first(fn ($p) => $p->full_name === $canonicalName);
            $this->assertNotNull($player, $canonicalName);
            $this->assertSame($nation, $player->nationality);
            $this->assertTrue(Nationality::isValid($nation));
            if ($status === 'ajouté') {
                $this->assertStringContainsString('Rang éditorial Coupe du monde : '.$rank.'.', $player->description);
                $this->assertStringContainsString('Poste : '.$position, $player->description);
                foreach ($player->stats as $value) {
                    $this->assertGreaterThanOrEqual(0, $value);
                    $this->assertLessThanOrEqual(100, $value);
                }
            }
            $count++;
        }
        fclose($source);
        $this->assertSame(44, $count);
        $this->assertSame('France', $players->firstWhere('lastname', 'Cantona')->nationality);
        $this->assertSame('🇵🇹', Nationality::flag('Portugal'));
        $this->assertSame('🇭🇷', Nationality::flag('Croatie'));
        $this->assertCount(61, $players->where('nationality', 'Belgique'));
    }

    public function test_migration_appends_only_missing_profiles_and_preserves_ids_edits_archives_and_saved_players(): void
    {
        // Reconstituer le catalogue avant extension : les 72 profils de soutien et les 41 Belges.
        $seeder = new PlayerSeeder();
        $nationalities = Roster::nationalities();
        $old = [];
        foreach (Roster::COUNTRIES as $country) {
            array_push($old, ...WorldLegendProfiles::players($country::ROSTER));
        }
        array_push($old, ...Belgium::players());
        foreach ($old as $player) DB::table('players')->insert($seeder->row(PlayerOrigin::LEGENDS, $player, $nationalities, []));
        $reference = Player::where('lastname', 'Beckenbauer')->firstOrFail();
        $reference->update(['cost' => 321]);
        $before = DB::table('players')->orderBy('id')->get()->keyBy('id')->toArray();
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $reference->id, 'shot' => 42]);
        $savedBefore = $saved->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_17_200000_add_missing_world_cup_legends.php');
        $migration->up();
        $this->assertSame(591, Player::where('origin', PlayerOrigin::LEGENDS)->count());
        foreach ($before as $id => $row) $this->assertEquals($row, DB::table('players')->find($id));
        $cantona = Player::where('lastname', 'Cantona')->firstOrFail();
        $cantona->delete();
        $migration->up();
        $this->assertSame(591, Player::withTrashed()->where('origin', PlayerOrigin::LEGENDS)->count());
        $this->assertSoftDeleted('players', ['id' => $cantona->id]);
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
    }

    public function test_a_mononym_can_be_edited_without_inventing_a_firstname(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $player = Player::factory()->create(['firstname' => '', 'lastname' => 'Pelé']);
        $payload = ['firstname' => '', 'lastname' => 'Pelé', 'position' => 'Forward', 'age' => 13, 'cost' => 100,
            'origin' => PlayerOrigin::LEGENDS, 'nationality' => 'Brésil', 'stats' => ['attack' => 30]];
        $this->actingAs($admin)->post(route('players.update', $player), $payload)->assertSessionHasNoErrors();
        $this->assertSame('', $player->fresh()->firstname);
        $this->assertSame('Pelé', $player->fresh()->full_name);
        $this->actingAs($admin)->post(route('players.store'), array_merge($payload, ['lastname' => 'Didi']))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('', Player::where('lastname', 'Didi')->firstOrFail()->firstname);
        $this->actingAs($admin)->post(route('players.store'), array_merge($payload, ['lastname' => '']))->assertSessionHasErrors('lastname');
        $save = $this->makeSave(['user_id' => $admin->id]);
        $saved = $this->makePlayer($save, ['firstname' => '', 'lastname' => 'Pelé', 'position' => 'Forward']);
        $this->actingAs($admin)->put(route('game-saves.players.update', ['gameSave' => $save, 'player' => $saved]),
            ['firstname' => '', 'lastname' => 'Pelé', 'position' => 'Forward', 'cost' => 100, 'nationality' => 'Brésil'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('', $saved->fresh()->firstname);
        $this->assertSame('Pelé', $saved->fresh()->full_name);

    }
}

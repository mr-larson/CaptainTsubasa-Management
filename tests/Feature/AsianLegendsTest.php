<?php

namespace Tests\Feature;

use App\Models\Player;
use Database\Seeders\PlayerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class AsianLegendsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_new_selections_have_career_portraits_balanced_specialties_and_anderlecht_players(): void
    {
        $this->seed(PlayerSeeder::class);
        $korea = Player::where('origin', 'world_legends')->where('nationality', 'Corée du Sud')->get();
        $saudi = Player::where('origin', 'world_legends')->where('nationality', 'Arabie saoudite')->get();
        $this->assertCount(11, $korea);
        $this->assertCount(12, $saudi);
        foreach ([$korea, $saudi] as $team) {
            $this->assertCount(1, $team->filter(fn ($p) => $p->position->value === 'Goalkeeper'));
            $this->assertCount(4, $team->filter(fn ($p) => $p->position->value === 'Defender'));
            foreach ($team as $player) {
                $this->assertNotNull($player->photo_path, $player->full_name);
                $this->assertFileExists(public_path('storage/'.$player->photo_path));
            }
        }
        $son = $korea->firstWhere('lastname', 'Son');
        $park = $korea->firstWhere('lastname', 'Park');
        $hong = $korea->firstWhere('lastname', 'Hong');
        $majed = $saudi->firstWhere('lastname', 'Abdullah');
        $ronaldo = Player::where('origin', 'world_legends')->where('nationality', 'Brésil')->where('lastname', 'Ronaldo')->sole();
        $this->assertGreaterThan($majed->stats['shot'], $son->stats['shot']);
        $this->assertLessThan($ronaldo->stats['shot'], $son->stats['shot']);
        $this->assertGreaterThan($son->stats['stamina'], $park->stats['stamina']);
        $this->assertGreaterThan($park->stats['defense'], $hong->stats['defense']);
        $this->assertNotNull($saudi->firstWhere('lastname', 'Al-Jaber'));
        $stoica = Player::where('origin', 'world_legends')->where('lastname', 'Stoica')->sole();
        $this->assertSame('Roumanie', $stoica->nationality);
        $this->assertSame('Midfielder', $stoica->position->value);
        $this->assertSame('players/alin-stoica-2002.jpg', $stoica->photo_path);
        $this->assertSame('players/par-zetterberg-anderlecht.jpg', Player::where('lastname', 'Zetterberg')->sole()->photo_path);
    }

    public function test_addition_preserves_manual_edits_archives_other_universes_and_saved_players(): void
    {
        // Les anciennes migrations peuvent déjà remplir un catalogue lors de l'installation des tests.
        foreach (json_decode(file_get_contents(database_path('data/anderlecht-asian-legends-2026-11-18.json')), true) as $row) {
            DB::table('players')->where('origin', 'world_legends')->where('firstname', $row['firstname'])->where('lastname', $row['lastname'])->delete();
        }
        $existing = Player::factory()->create(['firstname' => 'Alin', 'lastname' => 'Stoica', 'origin' => 'world_legends', 'nationality' => 'Roumanie', 'cost' => 999, 'photo_path' => 'players/personal.jpg']);
        $before = $existing->fresh()->getAttributes();
        $other = Player::factory()->create(['firstname' => 'Heung-min', 'lastname' => 'Son', 'origin' => 'original', 'nationality' => 'Corée du Sud']);
        $otherBefore = $other->fresh()->getAttributes();
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $existing->id]);
        $savedBefore = $saved->fresh()->getAttributes();
        $countBefore = Player::withTrashed()->where('origin', 'world_legends')->count();
        $migration = require database_path('migrations/2026_11_18_130000_add_stoica_and_asian_legends.php');
        $migration->up();
        $son = Player::where('origin', 'world_legends')->where('lastname', 'Son')->sole();
        $this->assertSame('players/heung-min-son-2014.webp', $son->photo_path);
        $son->delete();
        $migration->up();
        $this->assertSame($countBefore + 23, Player::withTrashed()->where('origin', 'world_legends')->count());
        $this->assertSoftDeleted('players', ['id' => $son->id]);
        $this->assertSame($before, $existing->fresh()->getAttributes());
        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
    }

    public function test_zetterberg_replacement_only_updates_the_known_imported_photo(): void
    {
        $player = Player::factory()->create(['firstname' => 'Pär', 'lastname' => 'Zetterberg', 'origin' => 'world_legends', 'nationality' => 'Suède', 'photo_path' => 'players/par-zetterberg.jpg']);
        $other = Player::factory()->create(['firstname' => 'Pär', 'lastname' => 'Zetterberg', 'origin' => 'original', 'nationality' => 'Suède', 'photo_path' => 'players/par-zetterberg.jpg']);
        $saved = $this->makePlayer($this->makeSave(), ['base_player_id' => $player->id, 'photo_path' => $player->photo_path]);
        $savedBefore = $saved->fresh()->getAttributes();
        $before = $player->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_18_131000_refresh_zetterberg_anderlecht_portrait.php');
        $migration->up();
        $migration->up();
        $this->assertSame('players/par-zetterberg-anderlecht.jpg', $player->fresh()->photo_path);
        $after = $player->fresh()->getAttributes();
        unset($before['photo_path'], $after['photo_path']);
        $this->assertSame($before, $after);
        $this->assertSame('players/par-zetterberg.jpg', $other->fresh()->photo_path);
        $this->assertSame($savedBefore, $saved->fresh()->getAttributes());
        $player->update(['photo_path' => 'players/custom.jpg']);
        $migration->up();
        $this->assertSame('players/custom.jpg', $player->fresh()->photo_path);
        $player->update(['photo_path' => 'players/par-zetterberg.jpg']);
        $player->delete();
        $migration->up();
        $this->assertSame('players/par-zetterberg.jpg', Player::withTrashed()->findOrFail($player->id)->photo_path);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\PlayerOrigin;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\PlayerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGameWorld;
use Tests\TestCase;

class InternationalRosterExpansionTest extends TestCase
{
    use RefreshDatabase;
    use BuildsGameWorld;

    public function test_seeds_separate_origins_and_preserves_eight_national_player_pools(): void
    {
        $this->seed(PlayerSeeder::class);
        $this->assertSame(591, Player::where('origin', PlayerOrigin::LEGENDS)->count());
        $this->assertSame('captain_tsubasa', Player::where('lastname', 'Ozora')->value('origin'));
        $this->assertSame(PlayerOrigin::INTERNATIONAL, Player::where('lastname', 'Schneider')->value('origin'));
        foreach (['Allemagne', 'France', 'Brésil', 'Argentine', 'Uruguay', 'Pays-Bas', 'Mexique', 'Italie'] as $nation) {
            $players = Player::where('nationality', $nation)->get();
            // Les viviers combinent les deux univers ; leur éligibilité en tournoi est vérifiée ailleurs.
            $this->assertNotEmpty($players->where('origin', PlayerOrigin::LEGENDS), $nation);
            $this->assertNotEmpty($players->where('origin', PlayerOrigin::INTERNATIONAL), $nation);
            $this->assertGreaterThanOrEqual(1, $players->where('position', 'Goalkeeper')->count(), $nation);
        }
        $this->assertLessThan(Player::where('lastname', 'Hyuga')->first()->stats['shot'],
            Player::where('origin', PlayerOrigin::LEGENDS)->where('nationality', '!=', 'Belgique')->get()->max(fn ($p) => $p->stats['shot']));
    }

    public function test_catalog_migration_is_repeatable_and_preserves_saved_players_and_edits(): void
    {
        $reference = Player::factory()->create(['origin' => 'captain_tsubasa', 'nationality' => 'Allemagne']);
        $save = $this->makeSave();
        $saved = $this->makePlayer($save, ['base_player_id' => $reference->id, 'origin' => 'captain_tsubasa', 'nationality' => 'Allemagne', 'shot' => 47]);
        $migration = require database_path('migrations/2026_11_17_160000_add_world_legends_and_separate_international_origin.php');
        $migration->up();
        $legend = Player::where('origin', PlayerOrigin::LEGENDS)->firstOrFail();
        $legend->update(['cost' => 123]);
        $migration->up();
        $this->assertSame(591, Player::where('origin', PlayerOrigin::LEGENDS)->count());
        $this->assertSame(123, $legend->fresh()->cost);
        $this->assertSame(PlayerOrigin::INTERNATIONAL, $reference->fresh()->origin);
        $this->assertSame('captain_tsubasa', $saved->fresh()->origin);
        $this->assertSame(47, $saved->fresh()->shot);
    }

    public function test_admin_can_assign_change_and_clear_nationality_and_origin(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $payload = ['firstname' => 'Test', 'lastname' => 'Nationalité', 'age' => 13,
            'position' => 'Defender', 'cost' => 40, 'stats' => ['speed' => 60], 'nationality' => 'France', 'origin' => PlayerOrigin::LEGENDS];
        $this->actingAs($admin)->post(route('players.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $player = Player::where('lastname', 'Nationalité')->firstOrFail();
        $this->assertSame('France', $player->nationality);
        $this->assertSame(PlayerOrigin::LEGENDS, $player->origin);
        $this->actingAs($admin)->post(route('players.update', $player), array_merge($payload, ['nationality' => 'Italie']))->assertSessionHasNoErrors();
        $this->assertSame('Italie', $player->fresh()->nationality);
        $this->actingAs($admin)->post(route('players.update', $player), array_merge($payload, ['nationality' => 'Atlantide']))->assertSessionHasErrors('nationality');
        $this->actingAs($admin)->post(route('players.update', $player), array_merge($payload, ['nationality' => null]))->assertSessionHasNoErrors();
        $this->assertNull($player->fresh()->nationality);
    }

    public function test_international_visibility_still_applies_to_new_origins_and_legacy_saves(): void
    {
        $save = $this->makeSave();
        $save->setConfig(['internationals_visible' => false, 'visible_origins' => [
            'captain_tsubasa' => true, PlayerOrigin::INTERNATIONAL => true, PlayerOrigin::LEGENDS => true,
        ]]);
        $japanese = $this->makePlayer($save, ['origin' => 'captain_tsubasa', 'nationality' => 'Japon']);
        $untagged = $this->makePlayer($save, ['origin' => null, 'nationality' => null]);
        $this->makePlayer($save, ['origin' => 'captain_tsubasa', 'nationality' => 'Allemagne']);
        $this->makePlayer($save, ['origin' => PlayerOrigin::INTERNATIONAL, 'nationality' => 'Allemagne']);
        $this->makePlayer($save, ['origin' => PlayerOrigin::LEGENDS, 'nationality' => 'France']);
        $visible = $save->gamePlayers()->visibleForConfig($save)->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$japanese->id, $untagged->id], $visible);
    }
}

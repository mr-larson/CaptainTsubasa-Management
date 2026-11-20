<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Services\WorldCup\NationalTeamAssembler;
use App\Services\WorldCup\NewWorldCupBuilder;
use Database\Seeders\PlayerSeeder;
use Database\Seeders\Support\NationalPool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Vivier national : les figurants des clubs japonais sont exclus de la sélection, pas des clubs. */
class NationalPoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_listed_japanese_club_characters_are_eligible(): void
    {
        $this->assertTrue(NationalPool::eligible('captain_tsubasa', 'Japon', 'Tsubasa Ozora'));
        $this->assertTrue(NationalPool::eligible('captain_tsubasa', 'Japon', 'Takeshi Kishida'));
        $this->assertFalse(NationalPool::eligible('captain_tsubasa', 'Japon', 'Susumu Sakurai'));
        // Les autres origines et nationalités ne sont jamais filtrées.
        $this->assertTrue(NationalPool::eligible('world_legends', 'Japon', 'Hidetoshi Nakata'));
        $this->assertTrue(NationalPool::eligible('captain_tsubasa', 'Brésil', 'Inconnu Total'));
        $this->assertCount(42, NationalPool::JAPAN_TSUBASA_ELIGIBLE);
    }

    public function test_extras_are_left_out_of_the_national_pool_and_the_playable_count(): void
    {
        Player::factory()->count(2)->create(['nationality' => 'Japon', 'origin' => 'captain_tsubasa', 'position' => 'Goalkeeper']);
        Player::factory()->count(10)->create(['nationality' => 'Japon', 'origin' => 'captain_tsubasa', 'position' => 'Forward']);
        $extras = Player::factory()->count(5)->create(['nationality' => 'Japon', 'origin' => 'captain_tsubasa', 'position' => 'Defender', 'national_eligible' => false]);

        $this->assertSame(12, Player::nationalEligible()->where('nationality', 'Japon')->count());
        $this->assertSame(17, Player::where('nationality', 'Japon')->count(), 'les figurants restent en base, donc jouables en club');

        $preview = app(NationalTeamAssembler::class)->preview('Japon');
        $this->assertNotNull($preview);
        $ids = collect($preview['players'])->pluck('id');
        $this->assertCount(12, $ids);
        $this->assertEmpty($ids->intersect($extras->pluck('id')));

        $japan = collect(app(NewWorldCupBuilder::class)->playableNations())->firstWhere('name', 'Japon');
        if ($japan !== null) {
            $this->assertSame(12, $japan['total']);
        }
    }

    public function test_a_fresh_seed_flags_the_extras_like_the_migration(): void
    {
        $this->seed(PlayerSeeder::class);

        $eligible = Player::nationalEligible()->where('nationality', 'Japon')->where('origin', 'captain_tsubasa')->get();
        $this->assertCount(42, $eligible);
        $this->assertEqualsCanonicalizing(NationalPool::JAPAN_TSUBASA_ELIGIBLE, $eligible->map->full_name->all());
        $this->assertSame(0, Player::nationalEligible()->where('origin', 'captain_tsubasa')->where('nationality', 'Japon')->whereIn('lastname', ['Sakurai'])->count());
    }
}

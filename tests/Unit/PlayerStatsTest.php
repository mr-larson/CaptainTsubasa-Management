<?php

namespace Tests\Unit;

use App\Models\Player;
use Tests\TestCase;

/**
 * Couche de stats du joueur, sur laquelle s'appuie le moteur de match :
 * - filet de sécurité DEFAULT_STATS quand une clé manque ;
 * - tolérance JSON (string) / array sur l'attribut stats ;
 * - accesseurs HasSoccerStats qui lisent à travers les stats.
 * Tests purs : aucun accès base de données.
 */
class PlayerStatsTest extends TestCase
{
    public function test_missing_stats_fall_back_to_defaults(): void
    {
        $player = new Player();
        $player->stats = ['shot' => 90]; // une seule clé fournie

        $stats = $player->stats;

        $this->assertSame(90, $stats['shot']); // valeur fournie conservée
        $this->assertSame(Player::DEFAULT_STATS['pass'], $stats['pass']); // clé absente => défaut

        foreach (array_keys(Player::DEFAULT_STATS) as $key) {
            $this->assertArrayHasKey($key, $stats);
        }
    }

    public function test_stats_accept_a_json_string(): void
    {
        $player = new Player();
        $player->stats = json_encode(['speed' => 77]);

        $this->assertSame(77, $player->stats['speed']);
    }

    public function test_invalid_stats_value_falls_back_to_defaults_only(): void
    {
        $player = new Player();
        $player->stats = 'not-json';

        $this->assertSame(Player::DEFAULT_STATS, $player->stats);
    }

    public function test_full_name_concatenates_first_and_last_name(): void
    {
        $player = new Player(['firstname' => 'Tsubasa', 'lastname' => 'Ozora']);

        $this->assertSame('Tsubasa Ozora', $player->full_name);
    }

    public function test_soccer_stat_helpers_read_through_to_stats(): void
    {
        $player = new Player();
        $player->stats = ['shot' => 88];

        $this->assertSame(88, $player->shotStat());                       // valeur fournie
        $this->assertSame(Player::DEFAULT_STATS['pass'], $player->passStat()); // défaut moteur
    }
}

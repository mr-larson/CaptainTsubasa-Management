<?php

namespace Tests\Unit;

use Database\Seeders\Players\EcoleDesChampions\EcoleStatFiles;
use Database\Seeders\Players\EcoleDesChampions\Roster;
use PHPUnit\Framework\TestCase;

/** Vérifie le fichier de stats de L'École des Champions : noms connus et valeurs sous les héros de Captain Tsubasa. */
class EcoleStatFilesTest extends TestCase
{
    public function test_every_overridden_player_exists_in_the_roster(): void
    {
        $names = array_map(fn ($p) => trim($p[0].' '.$p[1]), Roster::players());

        foreach (array_keys(EcoleStatFiles::overrides()) as $name) {
            $this->assertContains($name, $names, $name);
        }
    }

    public function test_user_fixed_values_are_applied_to_a_fresh_roster(): void
    {
        $stats = [];
        foreach (Roster::players() as $player) {
            $stats[trim($player[0].' '.$player[1])] = $player[5];
        }

        $this->assertSame(32, $stats['Cesare Gatti']['shot']);
        $this->assertSame(28, $stats['Cesare Gatti']['dribble']);
        $this->assertSame(22, $stats['Benjamin Lefranc']['tackle']);
        $this->assertSame(23, $stats['Benjamin Lefranc']['intercept']);
        $this->assertSame(28, $stats['Benjamin Lefranc']['shot']);
        $this->assertSame(26, $stats['Benjamin Lefranc']['dribble']);
        $this->assertSame(16, $stats['Yann Haarden']['block']);
    }

    public function test_overrides_stay_within_hero_ceilings(): void
    {
        foreach (EcoleStatFiles::overrides() as $name => $stats) {
            $this->assertLessThanOrEqual(39, $stats['attack'] ?? 0, $name);
            $this->assertLessThanOrEqual(33, $stats['shot'] ?? 0, $name);
            $this->assertGreaterThanOrEqual(8, min($stats), $name);
        }
    }
}

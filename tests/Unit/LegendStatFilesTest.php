<?php

namespace Tests\Unit;

use Database\Seeders\Players\InternationalLegends\Roster;
use Database\Seeders\Players\InternationalLegends\Support\LegendStatFiles;
use PHPUnit\Framework\TestCase;

/** Vérifie les fichiers de stats par nation : noms connus du catalogue et valeurs sous les héros de Captain Tsubasa. */
class LegendStatFilesTest extends TestCase
{
    public function test_every_overridden_player_exists_in_the_roster(): void
    {
        $names = array_map(fn ($p) => trim($p[0].' '.$p[1]), Roster::players());

        foreach (array_keys(LegendStatFiles::overrides()) as $name) {
            $this->assertContains($name, $names, $name);
        }
    }

    public function test_overrides_stay_under_tsubasa_ceilings(): void
    {
        foreach (LegendStatFiles::overrides() as $name => $stats) {
            $this->assertLessThanOrEqual(38, $stats['attack'] ?? 0, $name);
            $this->assertLessThanOrEqual(30, $stats['shot'] ?? 0, $name);
            $this->assertLessThanOrEqual(30, $stats['hand_save'] ?? 0, $name);
            $this->assertLessThanOrEqual(84, max($stats['speed'] ?? 0, $stats['stamina'] ?? 0), $name);
        }
    }
}

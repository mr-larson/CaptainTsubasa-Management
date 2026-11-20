<?php

namespace Tests\Unit;

use Database\Seeders\Players\CaptainTsubasa\Clubs\ClubStatFiles;
use Database\Seeders\Players\CaptainTsubasa\Clubs\Roster;
use PHPUnit\Framework\TestCase;

/** Vérifie les fichiers de stats par club : noms connus du catalogue et valeurs sous les héros de Captain Tsubasa. */
class ClubStatFilesTest extends TestCase
{
    public function test_every_overridden_player_exists_in_the_club_rosters(): void
    {
        $names = array_map(fn ($p) => trim($p[0].' '.$p[1]), Roster::players());

        foreach (array_keys(ClubStatFiles::overrides()) as $name) {
            $this->assertContains($name, $names, $name);
        }
    }

    public function test_overrides_stay_within_hero_ceilings(): void
    {
        foreach (ClubStatFiles::overrides() as $name => $stats) {
            $this->assertLessThanOrEqual(39, $stats['attack'] ?? 0, $name);
            // Hyuga (tir à 33) est le seul à dépasser 31 parmi les joueurs de club.
            $this->assertLessThanOrEqual(33, $stats['shot'] ?? 0, $name);
            $this->assertGreaterThanOrEqual(8, min($stats), $name);
        }
    }
}

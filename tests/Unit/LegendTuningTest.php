<?php

namespace Tests\Unit;

use Database\Seeders\Players\InternationalLegends\Support\LegendTuning;
use PHPUnit\Framework\TestCase;

/** Vérifie que l'individualisation des légendes reste cohérente et sous les héros de Captain Tsubasa. */
class LegendTuningTest extends TestCase
{
    private const FORWARD = ['speed' => 68, 'stamina' => 72, 'attack' => 30, 'defense' => 17, 'shot' => 25, 'pass' => 20, 'dribble' => 23, 'block' => 14, 'intercept' => 16, 'tackle' => 16, 'hand_save' => 10, 'punch_save' => 10];

    public function test_unlisted_player_is_untouched(): void
    {
        $this->assertSame(self::FORWARD, LegendTuning::adjust('Inconnu Total', 'Forward', self::FORWARD));
    }

    public function test_stature_orders_players_of_the_same_profile(): void
    {
        $star = LegendTuning::adjust('Luis Suárez', 'Forward', self::FORWARD);
        $minor = LegendTuning::adjust('Michaël Goossens', 'Forward', self::FORWARD);

        $this->assertGreaterThan($minor['shot'], $star['shot']);
        $this->assertGreaterThan($minor['attack'], $star['attack']);
    }

    public function test_stats_stay_under_tsubasa_ceilings(): void
    {
        foreach (array_keys(LegendTuning::TUNING) as $name) {
            $stats = LegendTuning::adjust($name, 'Forward', self::FORWARD);
            $this->assertLessThanOrEqual(38, $stats['attack'], $name);
            $this->assertLessThanOrEqual(30, $stats['shot'], $name);
            $this->assertGreaterThanOrEqual(10, min($stats), $name);
        }
    }
}

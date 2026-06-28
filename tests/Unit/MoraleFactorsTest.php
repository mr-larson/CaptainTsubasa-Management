<?php

namespace Tests\Unit;

use App\Models\GameSaves\GameMatch;
use App\Models\GameSaves\GamePlayer;
use App\Services\MoraleService;
use Tests\TestCase;

/**
 * Fonctions pures de MoraleService (sans base de données) : paliers de moral
 * utilisés par le moteur de match et la progression, et règle de refus de
 * re-signature. Ces seuils sont consommés ailleurs (MatchSimulator,
 * PostMatchProgressionService, SeasonService) → on les fige.
 */
class MoraleFactorsTest extends TestCase
{
    public function test_match_factor_bands(): void
    {
        $this->assertSame(0.90, MoraleService::matchFactor(20)); // révolté
        $this->assertSame(0.95, MoraleService::matchFactor(40)); // mécontent
        $this->assertSame(1.0, MoraleService::matchFactor(60));  // neutre
        $this->assertSame(1.02, MoraleService::matchFactor(80)); // satisfait
        $this->assertSame(1.05, MoraleService::matchFactor(95)); // galvanisé
        $this->assertSame(1.0, MoraleService::matchFactor(null), 'null → moral neutre.');
    }

    public function test_xp_factor_bands(): void
    {
        $this->assertSame(0.8, MoraleService::xpFactor(20));
        $this->assertSame(0.9, MoraleService::xpFactor(40));
        $this->assertSame(1.0, MoraleService::xpFactor(60));
        $this->assertSame(1.1, MoraleService::xpFactor(95));
    }

    public function test_refuses_to_sign_when_revolted_or_estranged(): void
    {
        $revolted = new GamePlayer(['morale' => 15, 'coach_affinity' => 0]);
        $this->assertTrue(MoraleService::refusesToSign($revolted), 'Moral révolté → refuse.');

        $estranged = new GamePlayer(['morale' => 60, 'coach_affinity' => -50]);
        $this->assertTrue(MoraleService::refusesToSign($estranged), 'Relation coach au plancher → refuse.');

        $content = new GamePlayer(['morale' => 60, 'coach_affinity' => 0]);
        $this->assertFalse(MoraleService::refusesToSign($content));
    }

    public function test_resolve_played_player_ids_reads_match_stats(): void
    {
        $match = new GameMatch();
        $match->match_stats = ['players' => ['7' => [], '11' => []]];

        $played = MoraleService::resolvePlayedPlayerIds($match);

        $this->assertArrayHasKey('7', $played);
        $this->assertArrayHasKey('11', $played);
        $this->assertTrue($played['7']);
    }
}

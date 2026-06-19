<?php

namespace Tests\Unit;

use App\Models\Contract;
use Tests\TestCase;

/**
 * Logique de cycle de vie d'un contrat (basée sur matches_total / matches_played).
 * Tests purs : aucun accès base de données.
 */
class ContractLogicTest extends TestCase
{
    public function test_matches_remaining_is_total_minus_played(): void
    {
        $contract = new Contract(['matches_total' => 5, 'matches_played' => 2]);

        $this->assertSame(3, $contract->matches_remaining);
    }

    public function test_matches_remaining_never_goes_negative(): void
    {
        $contract = new Contract(['matches_total' => 2, 'matches_played' => 5]);

        $this->assertSame(0, $contract->matches_remaining);
    }

    public function test_contract_is_expired_when_all_matches_are_played(): void
    {
        $expired = new Contract(['matches_total' => 3, 'matches_played' => 3]);

        $this->assertTrue($expired->isExpired());
        $this->assertTrue($expired->hasEnded());
        $this->assertFalse($expired->isCurrent());
    }

    public function test_contract_is_current_while_matches_remain(): void
    {
        $running = new Contract(['matches_total' => 3, 'matches_played' => 1]);

        $this->assertFalse($running->isExpired());
        $this->assertTrue($running->isCurrent());
    }
}

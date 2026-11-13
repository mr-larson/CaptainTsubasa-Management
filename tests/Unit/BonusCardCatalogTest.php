<?php

namespace Tests\Unit;

use App\Enums\BonusCardTarget;
use Database\Seeders\BonusCardSeeder;
use Tests\TestCase;

/** Le catalogue de cartes, assemblé depuis les familles de database/seeders/BonusCards. */
class BonusCardCatalogTest extends TestCase
{
    public function test_catalog_has_unique_complete_cards(): void
    {
        $cards = BonusCardSeeder::cards();
        $names = array_column($cards, 'name');

        $this->assertCount(42, $cards);
        $this->assertSame($names, array_values(array_unique($names)), 'Nom de carte en double.');

        foreach ($cards as $card) {
            foreach (['name', 'description', 'tier', 'target', 'execution_phase', 'effect_type', 'effect_value', 'base_weight', 'icon'] as $key) {
                $this->assertArrayHasKey($key, $card, "{$card['name']} : clé « {$key} » manquante");
            }
            $this->assertNotNull(BonusCardTarget::tryFrom($card['target']), "{$card['name']} : cible inconnue");
            $this->assertContains($card['tier'], ['bronze', 'silver', 'gold']);
            $this->assertContains($card['kind'] ?? 'bonus', ['bonus', 'malus']);
        }
    }
}

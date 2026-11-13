<?php

namespace Database\Seeders\BonusCards;

/**
 * Cartes de stamina : restaurent l'endurance de l'effectif.
 */
final class StaminaCards
{
    /** @return array<int, array<string, mixed>> */
    public static function cards(): array
    {
        return [
            // ── Stamina (Self) ─────────────────────────────────
            [
                'name'            => 'Séance de récupération',
                'description'     => 'Restaure 20 points de stamina à tous les joueurs de l\'effectif.',
                'tier'            => 'bronze',
                'target'          => 'self',
                'execution_phase' => 'immediate',
                'effect_type'     => 'stamina_boost',
                'effect_value'    => ['amount' => 20],
                'base_weight'     => 150,
                'icon'            => '💊',
            ],
            [
                'name'            => 'Stage intensif',
                'description'     => 'Restaure 30 points de stamina à tous les joueurs de l\'effectif.',
                'tier'            => 'silver',
                'target'          => 'self',
                'execution_phase' => 'immediate',
                'effect_type'     => 'stamina_boost',
                'effect_value'    => ['amount' => 30],
                'base_weight'     => 100,
                'icon'            => '🏃',
            ],
            [
                'name'            => 'Préparation olympique',
                'description'     => 'Restaure 40 points de stamina à tous les joueurs de l\'effectif.',
                'tier'            => 'gold',
                'target'          => 'self',
                'execution_phase' => 'immediate',
                'effect_type'     => 'stamina_boost',
                'effect_value'    => ['amount' => 40],
                'base_weight'     => 50,
                'icon'            => '🏅',
            ],
        ];
    }
}

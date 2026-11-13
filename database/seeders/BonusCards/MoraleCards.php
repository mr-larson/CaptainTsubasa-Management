<?php

namespace Database\Seeders\BonusCards;

/**
 * Cartes de moral : joueur ou équipe entière.
 */
final class MoraleCards
{
    /** @return array<int, array<string, mixed>> */
    public static function cards(): array
    {
        return [
            // ── Moral (Player) ─────────────────────────────────
            [
                'name'            => 'Mot d\'encouragement',
                'description'     => 'Redonne 10 points de moral à un joueur.',
                'tier'            => 'bronze',
                'target'          => 'player',
                'execution_phase' => 'immediate',
                'effect_type'     => 'morale_boost',
                'effect_value'    => ['amount' => 10],
                'base_weight'     => 120,
                'icon'            => '💬',
            ],
            [
                'name'            => 'Entretien individuel',
                'description'     => 'Redonne 20 points de moral à un joueur.',
                'tier'            => 'silver',
                'target'          => 'player',
                'execution_phase' => 'immediate',
                'effect_type'     => 'morale_boost',
                'effect_value'    => ['amount' => 20],
                'base_weight'     => 80,
                'icon'            => '🗣️',
            ],
            [
                'name'            => 'Soutien psychologique',
                'description'     => 'Redonne 35 points de moral à un joueur.',
                'tier'            => 'gold',
                'target'          => 'player',
                'execution_phase' => 'immediate',
                'effect_type'     => 'morale_boost',
                'effect_value'    => ['amount' => 35],
                'base_weight'     => 30,
                'icon'            => '🧠',
            ],
        ];
    }
}

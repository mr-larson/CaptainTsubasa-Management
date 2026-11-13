<?php

namespace Database\Seeders\BonusCards;

/**
 * Boosts de stats « ce match » (hook pre_match).
 */
final class MatchBoostCards
{
    /** @return array<int, array<string, mixed>> */
    public static function cards(): array
    {
        return [
            // ── Boost match (Match / pre_match) ────────────────
            [
                'name'            => 'Motivation pré-match',
                'description'     => '+5 en attaque pour tous les joueurs lors du prochain match.',
                'tier'            => 'bronze',
                'target'          => 'match',
                'execution_phase' => 'pre_match',
                'effect_type'     => 'stat_boost',
                'effect_value'    => ['stat' => 'attack', 'amount' => 5],
                'base_weight'     => 120,
                'icon'            => '📣',
            ],
            [
                'name'            => 'Discours du coach',
                'description'     => '+10 en attaque pour tous les joueurs lors du prochain match.',
                'tier'            => 'silver',
                'target'          => 'match',
                'execution_phase' => 'pre_match',
                'effect_type'     => 'stat_boost',
                'effect_value'    => ['stat' => 'attack', 'amount' => 10],
                'base_weight'     => 80,
                'icon'            => '🎤',
            ],
            [
                'name'            => 'Mental d\'acier',
                'description'     => '+15 en attaque et +20 stamina pour tous les joueurs lors du prochain match.',
                'tier'            => 'gold',
                'target'          => 'match',
                'execution_phase' => 'pre_match',
                'effect_type'     => 'stat_boost_and_stamina',
                'effect_value'    => ['stat' => 'attack', 'amount' => 15, 'stamina_bonus' => 20],
                'base_weight'     => 30,
                'icon'            => '🔥',
            ],
        ];
    }
}

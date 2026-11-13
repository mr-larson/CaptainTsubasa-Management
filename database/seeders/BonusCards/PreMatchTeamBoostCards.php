<?php

namespace Database\Seeders\BonusCards;

/**
 * Boosts d'équipe « ce match » (hook pre_match).
 */
final class PreMatchTeamBoostCards
{
    /** @return array<int, array<string, mixed>> */
    public static function cards(): array
    {
        return [
            // ── Boosts d'équipe "ce match" (hook pre_match) ─────
            [
                'name'            => 'Bloc défensif',
                'description'     => '+10 en défense pour tous les joueurs lors du prochain match.',
                'kind'            => 'bonus',
                'tier'            => 'silver',
                'target'          => 'match',
                'execution_phase' => 'pre_match',
                'effect_type'     => 'stat_boost',
                'effect_value'    => ['stat' => 'defense', 'amount' => 10],
                'base_weight'     => 70,
                'icon'            => '🧱',
            ],
            [
                'name'            => 'Pressing intense',
                'description'     => '+8 en vitesse pour tous les joueurs lors du prochain match.',
                'kind'            => 'bonus',
                'tier'            => 'silver',
                'target'          => 'match',
                'execution_phase' => 'pre_match',
                'effect_type'     => 'stat_boost',
                'effect_value'    => ['stat' => 'speed', 'amount' => 8],
                'base_weight'     => 65,
                'icon'            => '⚡',
            ],
        ];
    }
}

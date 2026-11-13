<?php

namespace Database\Seeders\BonusCards;

/**
 * Cartes de relation avec le coach.
 */
final class CoachRelationCards
{
    /** @return array<int, array<string, mixed>> */
    public static function cards(): array
    {
        return [
            // ── Relation coach (Player) ────────────────────────
            [
                'name'            => 'Geste de confiance',
                'description'     => 'Améliore de 10 points la relation d\'un joueur avec le coach.',
                'tier'            => 'bronze',
                'target'          => 'player',
                'execution_phase' => 'immediate',
                'effect_type'     => 'coach_affinity_boost',
                'effect_value'    => ['amount' => 10],
                'base_weight'     => 120,
                'icon'            => '🤝',
            ],
            [
                'name'            => 'Vote de confiance',
                'description'     => 'Améliore de 20 points la relation d\'un joueur avec le coach.',
                'tier'            => 'silver',
                'target'          => 'player',
                'execution_phase' => 'immediate',
                'effect_type'     => 'coach_affinity_boost',
                'effect_value'    => ['amount' => 20],
                'base_weight'     => 80,
                'icon'            => '💛',
            ],
            [
                'name'            => 'Lien indéfectible',
                'description'     => 'Améliore de 35 points la relation d\'un joueur avec le coach.',
                'tier'            => 'gold',
                'target'          => 'player',
                'execution_phase' => 'immediate',
                'effect_type'     => 'coach_affinity_boost',
                'effect_value'    => ['amount' => 35],
                'base_weight'     => 30,
                'icon'            => '🫂',
            ],

            // ══════════════════════════════════════════════════
            //   CARTES MALUS — ciblent l'adversaire du prochain match
            // ══════════════════════════════════════════════════
        ];
    }
}

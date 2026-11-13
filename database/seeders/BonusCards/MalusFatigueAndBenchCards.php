<?php

namespace Database\Seeders\BonusCards;

/**
 * Malus sur l'adversaire : fatigue des titulaires et titulaire consigné.
 */
final class MalusFatigueAndBenchCards
{
    /** @return array<int, array<string, mixed>> */
    public static function cards(): array
    {
        return [
            // ── Fatigue (−stamina aux titulaires adverses) ─────
            [
                'name'            => 'Nuit agitée',
                'description'     => 'Retire 10 points d’endurance aux titulaires de votre adversaire avant son prochain match.',
                'kind'            => 'malus',
                'tier'            => 'bronze',
                'target'          => 'opponent',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_stamina_drain',
                'effect_value'    => ['amount' => 10],
                'base_weight'     => 90,
                'icon'            => '😴',
            ],
            [
                'name'            => 'Sabotage du sommeil',
                'description'     => 'Retire 15 points d’endurance aux titulaires de votre adversaire avant son prochain match.',
                'kind'            => 'malus',
                'tier'            => 'silver',
                'target'          => 'opponent',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_stamina_drain',
                'effect_value'    => ['amount' => 15],
                'base_weight'     => 70,
                'icon'            => '🥱',
            ],
            [
                'name'            => 'Épuisement programmé',
                'description'     => 'Retire 20 points d’endurance aux titulaires de votre adversaire avant son prochain match.',
                'kind'            => 'malus',
                'tier'            => 'gold',
                'target'          => 'opponent',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_stamina_drain',
                'effect_value'    => ['amount' => 20],
                'base_weight'     => 35,
                'icon'            => '🪫',
            ],

            // ── Titulaire consigné (interdit de titularisation) ─
            [
                'name'            => 'Convocation surprise',
                'description'     => 'Un titulaire au hasard de votre adversaire ne pourra pas débuter son prochain match.',
                'kind'            => 'malus',
                'tier'            => 'silver',
                'target'          => 'opponent',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_bench_starter',
                'effect_value'    => ['count' => 1, 'pick' => 'random'],
                'base_weight'     => 60,
                'icon'            => '🚫',
            ],
            [
                'name'            => 'Scandale médiatique',
                'description'     => 'Un cadre de votre adversaire (son meilleur titulaire de champ) ne pourra pas débuter son prochain match.',
                'kind'            => 'malus',
                'tier'            => 'gold',
                'target'          => 'opponent',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_bench_starter',
                'effect_value'    => ['count' => 1, 'pick' => 'key'],
                'base_weight'     => 30,
                'icon'            => '📰',
            ],
        ];
    }
}

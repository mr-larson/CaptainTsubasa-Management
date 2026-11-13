<?php

namespace Database\Seeders\BonusCards;

/**
 * Cartes de blessure : réduisent ou soignent l'indisponibilité d'un joueur.
 */
final class InjuryCards
{
    /** @return array<int, array<string, mixed>> */
    public static function cards(): array
    {
        return [
            // ── Blessure (Player) ──────────────────────────────
            [
                'name'            => 'Kiné express',
                'description'     => 'Réduit de 2 semaines la durée d\'indisponibilité d\'un joueur blessé.',
                'tier'            => 'bronze',
                'target'          => 'player',
                'execution_phase' => 'immediate',
                'effect_type'     => 'injury_reduce',
                'effect_value'    => ['weeks' => 2],
                'base_weight'     => 120,
                'icon'            => '🩹',
            ],
            [
                'name'            => 'Chirurgie accélérée',
                'description'     => 'Réduit de 4 semaines la durée d\'indisponibilité d\'un joueur blessé.',
                'tier'            => 'silver',
                'target'          => 'player',
                'execution_phase' => 'immediate',
                'effect_type'     => 'injury_reduce',
                'effect_value'    => ['weeks' => 4],
                'base_weight'     => 80,
                'icon'            => '🏥',
            ],
            [
                'name'            => 'Miracle médical',
                'description'     => 'Guérit immédiatement un joueur blessé, quelle que soit la durée restante.',
                'tier'            => 'gold',
                'target'          => 'player',
                'execution_phase' => 'immediate',
                'effect_type'     => 'injury_cure',
                'effect_value'    => ['weeks' => 999],
                'base_weight'     => 30,
                'icon'            => '✨',
            ],
        ];
    }
}

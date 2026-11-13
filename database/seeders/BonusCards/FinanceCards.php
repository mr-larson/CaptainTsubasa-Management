<?php

namespace Database\Seeders\BonusCards;

/**
 * Cartes de finance : paris publicitaires et défis sponsors.
 */
final class FinanceCards
{
    /** @return array<int, array<string, mixed>> */
    public static function cards(): array
    {
        return [
            // ── Finance — Pub (pari : gain aléatoire ±) ─────────
            [
                'name'            => 'Affiche locale',
                'description'     => 'Campagne d\'affichage au résultat incertain : rapporte entre -100 GL et +400 GL au budget.',
                'tier'            => 'bronze',
                'target'          => 'finance',
                'execution_phase' => 'immediate',
                'effect_type'     => 'revenue_gamble',
                'effect_value'    => ['min' => -100, 'max' => 400],
                'base_weight'     => 80,
                'icon'            => '📣',
            ],
            [
                'name'            => 'Spot TV',
                'description'     => 'Spot télévisé au résultat incertain : rapporte entre -300 GL et +900 GL au budget.',
                'tier'            => 'silver',
                'target'          => 'finance',
                'execution_phase' => 'immediate',
                'effect_type'     => 'revenue_gamble',
                'effect_value'    => ['min' => -300, 'max' => 900],
                'base_weight'     => 60,
                'icon'            => '📺',
            ],
            [
                'name'            => 'Campagne nationale',
                'description'     => 'Grande campagne nationale au résultat incertain : rapporte entre -500 GL et +2200 GL au budget. Coup de pouce pour les équipes en difficulté.',
                'tier'            => 'gold',
                'target'          => 'finance',
                'execution_phase' => 'immediate',
                'effect_type'     => 'revenue_gamble',
                'effect_value'    => ['min' => -500, 'max' => 2200],
                'base_weight'     => 30,
                'icon'            => '🎰',
            ],

            // ── Finance — Sponsor (défi : récompense si objectif atteint au prochain match) ──
            [
                'name'            => 'Prime de victoire',
                'description'     => 'Le sponsor verse +600 GL si tu gagnes ton prochain match. Sinon, la mise est perdue.',
                'tier'            => 'bronze',
                'target'          => 'finance',
                'execution_phase' => 'immediate',
                'effect_type'     => 'revenue_challenge',
                'effect_value'    => ['challenge' => 'win', 'reward' => 600],
                'base_weight'     => 70,
                'icon'            => '🥅',
            ],
            [
                'name'            => 'Prime offensive',
                'description'     => 'Le sponsor verse +1200 GL si ton équipe marque au moins 3 buts au prochain match. Sinon, la mise est perdue.',
                'tier'            => 'silver',
                'target'          => 'finance',
                'execution_phase' => 'immediate',
                'effect_type'     => 'revenue_challenge',
                'effect_value'    => ['challenge' => 'score_3', 'reward' => 1200],
                'base_weight'     => 60,
                'icon'            => '⚽',
            ],
            [
                'name'            => 'Prime défensive',
                'description'     => 'Le sponsor verse +1200 GL si ton équipe n\'encaisse aucun but au prochain match. Sinon, la mise est perdue.',
                'tier'            => 'silver',
                'target'          => 'finance',
                'execution_phase' => 'immediate',
                'effect_type'     => 'revenue_challenge',
                'effect_value'    => ['challenge' => 'clean_sheet', 'reward' => 1200],
                'base_weight'     => 60,
                'icon'            => '🛡️',
            ],
            [
                'name'            => 'Prime du spectacle',
                'description'     => 'Le sponsor verse +1400 GL si tu gagnes ton prochain match avec au moins 2 buts d\'écart. Sinon, la mise est perdue.',
                'tier'            => 'silver',
                'target'          => 'finance',
                'execution_phase' => 'immediate',
                'effect_type'     => 'revenue_challenge',
                'effect_value'    => ['challenge' => 'win_by_2', 'reward' => 1400],
                'base_weight'     => 50,
                'icon'            => '🎇',
            ],
            [
                'name'            => 'Méga-contrat',
                'description'     => 'Le sponsor verse +2500 GL si tu gagnes ton prochain match en marquant au moins 3 buts. Sinon, la mise est perdue.',
                'tier'            => 'gold',
                'target'          => 'finance',
                'execution_phase' => 'immediate',
                'effect_type'     => 'revenue_challenge',
                'effect_value'    => ['challenge' => 'win_score_3', 'reward' => 2500],
                'base_weight'     => 25,
                'icon'            => '🏆',
            ],
        ];
    }
}

<?php

namespace Database\Seeders\BonusCards;

/**
 * Malus à cible choisie (équipe au choix, leader) et debuffs de stats « ce match ».
 */
final class MalusTargetedCards
{
    /** @return array<int, array<string, mixed>> */
    public static function cards(): array
    {
        return [
            // ── Malus à cible choisie (équipe au choix / leader) ───
            [
                'name'            => 'Coup monté',
                'description'     => 'Choisissez une équipe : un de ses titulaires au hasard ne pourra pas débuter son prochain match.',
                'kind'            => 'malus',
                'tier'            => 'silver',
                'target'          => 'team',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_bench_starter',
                'effect_value'    => ['count' => 1, 'pick' => 'random', 'target_mode' => 'select'],
                'base_weight'     => 50,
                'icon'            => '🎯',
            ],
            [
                'name'            => 'Sabotage ciblé',
                'description'     => 'Choisissez une équipe : retire 15 points d’endurance à ses titulaires avant son prochain match.',
                'kind'            => 'malus',
                'tier'            => 'silver',
                'target'          => 'team',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_stamina_drain',
                'effect_value'    => ['amount' => 15, 'target_mode' => 'select'],
                'base_weight'     => 50,
                'icon'            => '😈',
            ],
            [
                'name'            => 'Abattre le favori',
                'description'     => 'Cible automatiquement le 1er du classement : son meilleur titulaire de champ ne pourra pas débuter son prochain match.',
                'kind'            => 'malus',
                'tier'            => 'gold',
                'target'          => 'team',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_bench_starter',
                'effect_value'    => ['count' => 1, 'pick' => 'key', 'target_mode' => 'leader'],
                'base_weight'     => 35,
                'icon'            => '👑',
            ],

            // ══════════════════════════════════════════════════
            //   CARTES BONUS — profondeur (cohésion, économie, défense)
            // ══════════════════════════════════════════════════
            [
                'name'            => 'Cellule de crise',
                'description'     => 'Pose un bouclier : le prochain malus infligé à votre équipe sera automatiquement annulé.',
                'kind'            => 'bonus',
                'tier'            => 'silver',
                'target'          => 'self',
                'execution_phase' => 'immediate',
                'effect_type'     => 'malus_shield',
                'effect_value'    => [],
                'base_weight'     => 70,
                'icon'            => '🛡️',
            ],
            [
                'name'            => 'Esprit d\'équipe',
                'description'     => 'Séminaire de cohésion : +15 de moral à tous les joueurs de l\'effectif.',
                'kind'            => 'bonus',
                'tier'            => 'silver',
                'target'          => 'self',
                'execution_phase' => 'immediate',
                'effect_type'     => 'team_morale_boost',
                'effect_value'    => ['amount' => 15],
                'base_weight'     => 70,
                'icon'            => '💛',
            ],
            [
                'name'            => 'Vente de maillots',
                'description'     => 'Revenu indexé sur votre dernier match : 200 GL + 150 GL/but marqué + 500 GL si victoire.',
                'kind'            => 'bonus',
                'tier'            => 'silver',
                'target'          => 'finance',
                'execution_phase' => 'immediate',
                'effect_type'     => 'revenue_performance',
                'effect_value'    => ['base' => 200, 'per_goal' => 150, 'win_bonus' => 500],
                'base_weight'     => 60,
                'icon'            => '👕',
            ],

            // ══════════════════════════════════════════════════
            //   CARTES MALUS — moral / relation / économie (cible variable)
            // ══════════════════════════════════════════════════
            [
                'name'            => 'Guerre psychologique',
                'description'     => 'Sape le moral de l\'adversaire de votre prochain match : −15 de moral à tout son effectif.',
                'kind'            => 'malus',
                'tier'            => 'silver',
                'target'          => 'opponent',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_morale_drain',
                'effect_value'    => ['amount' => 15],
                'base_weight'     => 55,
                'icon'            => '😤',
            ],
            [
                'name'            => 'Tapage médiatique',
                'description'     => 'Choisissez une équipe : −20 de relation entre son cadre (meilleur joueur de champ) et son coach.',
                'kind'            => 'malus',
                'tier'            => 'silver',
                'target'          => 'team',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_affinity_drain',
                'effect_value'    => ['amount' => 20, 'target_mode' => 'select'],
                'base_weight'     => 50,
                'icon'            => '🗞️',
            ],
            [
                'name'            => 'Tapage médiatique — Or',
                'description'     => 'Choisissez une équipe : −30 de relation entre son cadre (meilleur joueur de champ) et son coach.',
                'kind'            => 'malus',
                'tier'            => 'gold',
                'target'          => 'team',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_affinity_drain',
                'effect_value'    => ['amount' => 30, 'target_mode' => 'select'],
                'base_weight'     => 35,
                'icon'            => '🗞️',
            ],
            [
                'name'            => 'Contrôle fiscal',
                'description'     => 'Cible automatiquement le 1er du classement : −600 GL sur son budget.',
                'kind'            => 'malus',
                'tier'            => 'gold',
                'target'          => 'team',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_budget_drain',
                'effect_value'    => ['amount' => 600, 'target_mode' => 'leader'],
                'base_weight'     => 35,
                'icon'            => '💸',
            ],

            // ── Malus "ce match" : debuff de stats sur la cible ─
            [
                'name'            => 'Pelouse sabotée',
                'description'     => 'Terrain gras : −12 de vitesse à toute l\'équipe de votre prochain adversaire, pour ce match.',
                'kind'            => 'malus',
                'tier'            => 'silver',
                'target'          => 'opponent',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_team_stat_debuff',
                'effect_value'    => ['stat' => 'speed', 'amount' => 12],
                'base_weight'     => 50,
                'icon'            => '🌧️',
            ],
            [
                'name'            => 'Marquage à la culotte',
                'description'     => 'Choisissez une équipe : son cadre (meilleur joueur de champ) joue son prochain match avec −15 en attaque et −15 au tir.',
                'kind'            => 'malus',
                'tier'            => 'silver',
                'target'          => 'team',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_key_stat_debuff',
                'effect_value'    => ['stats' => ['attack' => 15, 'shot' => 15], 'target_mode' => 'select'],
                'base_weight'     => 45,
                'icon'            => '🥅',
            ],
        ];
    }
}

<?php

namespace Database\Seeders\BonusCards;

/**
 * Cartes « remontada » (les objets de fin de peloton) : bonus réservés au bas du classement
 * (`rank_gate` : `bottom_3` = 3 derniers, `bottom_half` = moitié basse) et malus visant
 * l'équipe classée juste devant (`target_mode` = `ahead`) ou le leader, plus le « Piège » (ouvert à tous).
 * La boutique ne les propose qu'aux équipes concernées (cf. BonusCard::isOfferableAt).
 */
final class ComebackCards
{
    /** @return array<int, array<string, mixed>> */
    public static function cards(): array
    {
        return [
            [
                'name'            => 'Remontada',
                'description'     => 'Réservée aux 3 derniers : +10 en attaque, en défense et en vitesse pour tous vos joueurs lors du prochain match.',
                'kind'            => 'bonus',
                'tier'            => 'gold',
                'target'          => 'match',
                'execution_phase' => 'pre_match',
                'effect_type'     => 'stat_boost',
                'effect_value'    => ['stats' => ['attack' => 10, 'defense' => 10, 'speed' => 10], 'rank_gate' => 'bottom_3'],
                'base_weight'     => 90,
                'icon'            => '🚀',
            ],
            [
                'name'            => 'Rage de l\'outsider',
                'description'     => 'Réservée à la moitié basse du classement : au prochain match, +1 en attaque et en défense par point de retard sur votre adversaire (de +3 à +15).',
                'kind'            => 'bonus',
                'tier'            => 'silver',
                'target'          => 'match',
                'execution_phase' => 'pre_match',
                'effect_type'     => 'underdog_stat_boost',
                'effect_value'    => ['min' => 3, 'max' => 15, 'boosted_stats' => ['attack', 'defense'], 'rank_gate' => 'bottom_half'],
                'base_weight'     => 110,
                'icon'            => '🔥',
            ],
            [
                'name'            => 'État de grâce',
                'description'     => 'Réservée aux 3 derniers : endurance et moral remis au maximum pour tout l\'effectif, et le prochain malus subi est annulé.',
                'kind'            => 'bonus',
                'tier'            => 'gold',
                'target'          => 'self',
                'execution_phase' => 'immediate',
                'effect_type'     => 'team_recovery',
                'effect_value'    => ['stamina' => 100, 'morale' => 100, 'shield' => true, 'rank_gate' => 'bottom_3'],
                'base_weight'     => 70,
                'icon'            => '⭐',
            ],
            [
                'name'            => 'Croc-en-jambe',
                'description'     => 'Vise l\'équipe classée juste devant vous : −10 d\'endurance pour 3 de ses titulaires de champ, tirés au hasard.',
                'kind'            => 'malus',
                'tier'            => 'bronze',
                'target'          => 'team',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_partial_stamina_drain',
                'effect_value'    => ['amount' => 10, 'count' => 3, 'target_mode' => 'ahead'],
                'base_weight'     => 90,
                'icon'            => '🦵',
            ],
            [
                'name'            => 'Marquage serré',
                'description'     => 'Vise l\'équipe classée juste devant vous : son meilleur joueur de champ perd 15 en attaque et en tir pour son prochain match.',
                'kind'            => 'malus',
                'tier'            => 'silver',
                'target'          => 'team',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_key_stat_debuff',
                'effect_value'    => ['stats' => ['attack' => 15, 'shot' => 15], 'target_mode' => 'ahead'],
                'base_weight'     => 60,
                'icon'            => '⛓️',
            ],
            [
                'name'            => 'Pression du favori',
                'description'     => 'Réservée à la moitié basse du classement : le 1er du classement perd 8 en attaque, en défense et en vitesse pour son prochain match.',
                'kind'            => 'malus',
                'tier'            => 'silver',
                'target'          => 'team',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_team_stat_debuff',
                'effect_value'    => ['stats' => ['attack' => 8, 'defense' => 8, 'speed' => 8], 'target_mode' => 'leader', 'rank_gate' => 'bottom_half'],
                'base_weight'     => 90,
                'icon'            => '🌪️',
            ],

            // ── Lot 2 : piège, vol de budget au leader, défi de l'exploit ──
            [
                'name'            => 'Piège',
                'description'     => 'Pose un piège : le prochain malus qui vise votre équipe est renvoyé à son expéditeur, qui le subit à votre place.',
                'kind'            => 'bonus',
                'tier'            => 'silver',
                'target'          => 'self',
                'execution_phase' => 'immediate',
                'effect_type'     => 'malus_reflect',
                'effect_value'    => [],
                'base_weight'     => 60,
                'icon'            => '🍌',
            ],
            [
                'name'            => 'Robin des Bois',
                'description'     => 'Réservée à la moitié basse du classement : prend jusqu\'à 500 GL au 1er du classement et les verse dans vos caisses (seulement ce qu\'il possède s\'il a moins).',
                'kind'            => 'malus',
                'tier'            => 'gold',
                'target'          => 'team',
                'execution_phase' => 'immediate',
                'effect_type'     => 'opponent_budget_steal',
                'effect_value'    => ['amount' => 500, 'target_mode' => 'leader', 'rank_gate' => 'bottom_half'],
                'base_weight'     => 60,
                'icon'            => '💰',
            ],
            [
                'name'            => 'Prime de l\'exploit',
                'description'     => 'Réservée à la moitié basse du classement : 1500 GL si vous battez votre prochain adversaire, à condition qu\'il soit mieux classé que vous. Sinon, la mise est perdue.',
                'kind'            => 'bonus',
                'tier'            => 'silver',
                'target'          => 'finance',
                'execution_phase' => 'immediate',
                'effect_type'     => 'revenue_challenge',
                'effect_value'    => ['challenge' => 'upset', 'reward' => 1500, 'rank_gate' => 'bottom_half'],
                'base_weight'     => 80,
                'icon'            => '🏆',
            ],
        ];
    }
}

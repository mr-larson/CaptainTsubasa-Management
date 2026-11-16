<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration de données : ajoute (ou met à jour) les 6 cartes « remontada » dans `bonus_cards` —
 * bonus réservés au bas du classement et malus visant l'équipe classée juste devant ou le leader.
 * Les valeurs sont figées ici (aucune classe de l'application importée). `down()` les supprime.
 * Aucun changement de schéma.
 */
return new class extends Migration
{
    /** @return array<int, array<string, mixed>> les 6 cartes, telles qu'elles étaient à la date de la migration */
    private function cards(): array
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
        ];
    }

    /** Insère ou met à jour chaque carte (par nom), au prix boutique du moment. */
    public function up(): void
    {
        foreach ($this->cards() as $card) {
            DB::table('bonus_cards')->updateOrInsert(
                ['name' => $card['name']],
                [
                    ...$card,
                    'effect_value' => json_encode($card['effect_value'], JSON_THROW_ON_ERROR),
                    'cost'         => 200, // prix boutique au moment de la migration
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ],
            );
        }
    }

    /** Supprime les 6 cartes (et, par cascade, leurs exemplaires achetés). */
    public function down(): void
    {
        DB::table('bonus_cards')->whereIn('name', array_column($this->cards(), 'name'))->delete();
    }
};

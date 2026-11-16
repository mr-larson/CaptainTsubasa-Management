<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration de données : ajoute (ou met à jour) 3 cartes dans `bonus_cards` — « Piège » (renvoie le
 * prochain malus), « Robin des Bois » (prend du budget au leader) et « Prime de l'exploit » (défi
 * sponsor contre un adversaire mieux classé). Les valeurs sont figées ici (aucune classe de
 * l'application importée). `down()` les supprime. Aucun changement de schéma.
 */
return new class extends Migration
{
    /** @return array<int, array<string, mixed>> les 3 cartes, telles qu'elles étaient à la date de la migration */
    private function cards(): array
    {
        return [
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

    /** Supprime les 3 cartes (et, par cascade, leurs exemplaires achetés). */
    public function down(): void
    {
        DB::table('bonus_cards')->whereIn('name', array_column($this->cards(), 'name'))->delete();
    }
};

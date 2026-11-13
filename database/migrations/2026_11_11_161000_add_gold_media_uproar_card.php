<?php

use App\Models\BonusCard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration de données : ajoute (ou met à jour) la carte malus or « Tapage médiatique — Or » (−30 de relation) dans `bonus_cards`. `down()` la supprime. Aucun changement de schéma.
 */
return new class extends Migration
{
    /** Insère la carte or. */
    public function up(): void
    {
        DB::table('bonus_cards')->updateOrInsert(
            ['name' => 'Tapage médiatique — Or'],
            [
                'description' => 'Choisissez une équipe : −30 de relation entre son cadre (meilleur joueur de champ) et son coach.',
                'kind' => 'malus',
                'tier' => 'gold',
                'target' => 'team',
                'execution_phase' => 'immediate',
                'effect_type' => 'opponent_affinity_drain',
                'effect_value' => json_encode(['amount' => 30, 'target_mode' => 'select'], JSON_THROW_ON_ERROR),
                'cost' => BonusCard::SHOP_PRICE,
                'base_weight' => 35,
                'icon' => '🗞️',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('bonus_cards')
            ->where('name', 'Tapage médiatique — Or')
            ->where('effect_type', 'opponent_affinity_drain')
            ->where('tier', 'gold')
            ->delete();
    }
};

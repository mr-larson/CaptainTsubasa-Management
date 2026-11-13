<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration de données : rééquilibre à la baisse la perte d'endurance de quatre cartes malus d'équipe (`opponent_stamina_drain`) en mettant à jour `effect_value` et la description. `down()` restaure les anciennes valeurs. Aucun changement de schéma.
 */
return new class extends Migration
{
    /** Applique les nouveaux montants d'endurance. */
    public function up(): void
    {
        $this->updateCards([
            'Nuit agitée' => 10,
            'Sabotage du sommeil' => 15,
            'Épuisement programmé' => 20,
            'Sabotage ciblé' => 15,
        ]);
    }

    public function down(): void
    {
        $this->updateCards([
            'Nuit agitée' => 15,
            'Sabotage du sommeil' => 25,
            'Épuisement programmé' => 35,
            'Sabotage ciblé' => 25,
        ]);
    }

    private function updateCards(array $amounts): void
    {
        foreach ($amounts as $name => $amount) {
            $card = DB::table('bonus_cards')
                ->where('name', $name)
                ->where('effect_type', 'opponent_stamina_drain')
                ->first();
            if (!$card) continue;

            $effect = json_decode($card->effect_value, true, flags: JSON_THROW_ON_ERROR);
            $effect['amount'] = $amount;
            $target = $card->target === 'team' ? 'de l’équipe choisie' : 'de votre prochain adversaire';
            DB::table('bonus_cards')->where('id', $card->id)->update([
                'effect_value' => json_encode($effect, JSON_THROW_ON_ERROR),
                'description' => "Retire {$amount} points d’endurance aux titulaires {$target}.",
            ]);
        }
    }
};

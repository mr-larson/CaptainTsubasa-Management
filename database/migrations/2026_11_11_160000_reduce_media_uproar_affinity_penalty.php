<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updatePenalty(20);
    }

    public function down(): void
    {
        $this->updatePenalty(30);
    }

    private function updatePenalty(int $amount): void
    {
        $cards = DB::table('bonus_cards')
            ->where('name', 'Tapage médiatique')
            ->where('effect_type', 'opponent_affinity_drain')
            ->where('tier', 'silver')
            ->get(['id', 'effect_value']);

        foreach ($cards as $card) {
            $effect = json_decode($card->effect_value ?? '{}', true) ?? [];
            $effect['amount'] = $amount;

            DB::table('bonus_cards')->where('id', $card->id)->update([
                'effect_value' => json_encode($effect, JSON_THROW_ON_ERROR),
                'description' => "Choisissez une équipe : −{$amount} de relation entre son cadre (meilleur joueur de champ) et son coach.",
            ]);
        }
    }
};

<?php

namespace App\Http\Requests\GameSaves;

/** Requête d'achat d'une carte bonus dans la boutique. */
class BuyBonusCardRequest extends GameSaveActionRequest
{
    /** Carte du catalogue à acheter et palier choisi (bronze, argent ou or). */
    public function rules(): array
    {
        return [
            'bonus_card_id' => ['required', 'integer', 'exists:bonus_cards,id'],
            'tier'          => ['required', 'in:bronze,silver,gold'],
        ];
    }
}

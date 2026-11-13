<?php

namespace App\Http\Requests\GameSaves;

class BuyBonusCardRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'bonus_card_id' => ['required', 'integer', 'exists:bonus_cards,id'],
            'tier'          => ['required', 'in:bronze,silver,gold'],
        ];
    }
}

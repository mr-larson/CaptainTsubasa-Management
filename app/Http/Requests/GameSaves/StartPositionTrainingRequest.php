<?php

namespace App\Http\Requests\GameSaves;

use App\Enums\PlayerPosition;
use Illuminate\Validation\Rules\Enum;

class StartPositionTrainingRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'player_id' => ['required', 'integer'],
            'position'  => ['required', new Enum(PlayerPosition::class)],
        ];
    }
}

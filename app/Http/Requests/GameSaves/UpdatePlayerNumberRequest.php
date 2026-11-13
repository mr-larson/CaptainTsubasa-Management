<?php

namespace App\Http\Requests\GameSaves;

class UpdatePlayerNumberRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'number' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}

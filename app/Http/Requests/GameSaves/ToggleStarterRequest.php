<?php

namespace App\Http\Requests\GameSaves;

class ToggleStarterRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'slot' => ['nullable', 'integer', 'between:1,11'],
        ];
    }
}

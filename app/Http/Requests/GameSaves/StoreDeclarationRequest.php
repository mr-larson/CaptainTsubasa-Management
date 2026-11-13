<?php

namespace App\Http\Requests\GameSaves;

use Illuminate\Validation\Rule;

class StoreDeclarationRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['praise', 'criticize'])],
        ];
    }
}

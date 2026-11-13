<?php

namespace App\Http\Requests\GameSaves;

use App\Services\PromiseService;
use Illuminate\Validation\Rule;

class StorePromiseRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(array_keys(PromiseService::TYPES))],
        ];
    }
}

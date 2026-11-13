<?php

namespace App\Http\Requests\GameSaves;

use App\Services\PromiseService;
use Illuminate\Validation\Rule;

/** Requête de promesse faite par le manager à un joueur. */
class StorePromiseRequest extends GameSaveActionRequest
{
    /** Type de promesse, parmi ceux définis dans PromiseService::TYPES (facultatif). */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(array_keys(PromiseService::TYPES))],
        ];
    }
}

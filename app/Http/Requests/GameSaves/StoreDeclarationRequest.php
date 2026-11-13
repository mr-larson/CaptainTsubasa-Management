<?php

namespace App\Http\Requests\GameSaves;

use Illuminate\Validation\Rule;

/** Requête de déclaration publique du manager envers un joueur (éloge ou critique). */
class StoreDeclarationRequest extends GameSaveActionRequest
{
    /** Type de déclaration : « praise » (éloge) ou « criticize » (critique). */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['praise', 'criticize'])],
        ];
    }
}

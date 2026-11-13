<?php

namespace App\Http\Requests\GameSaves;

class RenewContractRequest extends GameSaveActionRequest
{
    public function rules(): array
    {
        return [
            'weeks'    => ['required', 'integer', 'min:1'],
            'end_week' => ['required', 'integer', 'min:1'],
        ];
    }
}

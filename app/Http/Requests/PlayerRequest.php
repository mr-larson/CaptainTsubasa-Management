<?php

// App\Http\Requests\PlayerRequest.php

namespace App\Http\Requests;

use App\Enums\PlayerPosition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class PlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname'  => ['required', 'string', 'max:255'],
            'age'       => ['required', 'integer', 'min:1'],
            'position'  => ['required', new Enum(PlayerPosition::class)],
            'cost'      => ['required', 'numeric', 'min:0'],

            // on envoie bien un objet stats complet
            'stats'            => ['required', 'array'],
            'stats.attack'     => ['required', 'integer', 'min:0', 'max:100'],
            'stats.defender'   => ['required', 'integer', 'min:0', 'max:100'],
            'stats.speed'      => ['required', 'integer', 'min:0', 'max:100'],
            'stats.stamina'    => ['required', 'integer', 'min:0', 'max:100'],

            'description' => ['nullable', 'string'],
        ];
    }
}

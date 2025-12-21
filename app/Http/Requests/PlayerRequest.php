<?php

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
            'cost'      => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:4096'],


            // ─────────────────────────────
            //   STATS : complètement optionnelles
            //   (les seeders remplissent déjà tout)
            // ─────────────────────────────
            'stats'            => ['nullable', 'array'],

            // base stats
            'stats.attack'     => ['nullable', 'integer', 'min:0', 'max:100'],
            'stats.defense'    => ['nullable', 'integer', 'min:0', 'max:100'],
            'stats.speed'      => ['nullable', 'integer', 'min:0', 'max:100'],
            'stats.stamina'    => ['nullable', 'integer', 'min:0', 'max:100'],

            // skills détaillées (si un jour tu les édites à la main)
            'stats.shot'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'stats.pass'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'stats.dribble'    => ['nullable', 'integer', 'min:0', 'max:100'],
            'stats.block'      => ['nullable', 'integer', 'min:0', 'max:100'],
            'stats.intercept'  => ['nullable', 'integer', 'min:0', 'max:100'],
            'stats.tackle'     => ['nullable', 'integer', 'min:0', 'max:100'],
            'stats.hand_save'  => ['nullable', 'integer', 'min:0', 'max:100'],
            'stats.punch_save' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}

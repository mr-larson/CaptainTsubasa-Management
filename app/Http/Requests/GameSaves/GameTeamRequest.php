<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L’ownership est vérifiée dans le contrôleur GameTeamController.
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'budget'      => ['required', 'integer', 'min:0'],

            'wins'        => ['nullable', 'integer', 'min:0'],
            'draws'       => ['nullable', 'integer', 'min:0'],
            'losses'      => ['nullable', 'integer', 'min:0'],

            // Gestion du logo
            'logo'        => ['nullable', 'image', 'max:4096'],
            'remove_logo' => ['boolean'],

            // Seulement en cas de préparation pour edit()
            'logo_path'   => ['nullable', 'string'],
        ];
    }
}

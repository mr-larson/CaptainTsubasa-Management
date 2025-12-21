<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'wins'   => $this->input('wins', 0),
            'draws'  => $this->input('draws', 0),
            'losses' => $this->input('losses', 0),
            // si tu gardes budget required, tu peux retirer cette ligne
            // 'budget' => $this->input('budget', 0),
        ]);
    }

    public function rules(): array
    {
        $teamId = $this->route('team')?->id;

        return [
            'name'        => ['required', 'string', 'max:255', Rule::unique('teams', 'name')->ignore($teamId)],
            'description' => ['nullable', 'string'],
            'budget'      => ['required', 'integer', 'min:0'],
            'wins'        => ['nullable', 'integer', 'min:0'],
            'draws'       => ['nullable', 'integer', 'min:0'],
            'losses'      => ['nullable', 'integer', 'min:0'],

            'logo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }
}

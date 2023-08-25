<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',  // Nom obligatoire et au maximum 255 caractères
            'logo_path' => 'nullable|string|max:255',  // Chemin du logo optionnel, au maximum 255 caractères
            'budget' => 'required|integer|min:0',  // Budget obligatoire, entier et positif

        ];
    }
}

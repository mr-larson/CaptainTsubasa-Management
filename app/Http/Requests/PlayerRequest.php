<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlayerRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'image_path' => 'required|string|max:255',
            'nationality' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'height' => 'required|integer',
            'weight' => 'required|integer',
            'period' => 'required',
            'current_team_id' => 'required|integer|exists:teams,id',
            'stats' => 'required|array',
            'positions' => 'required|array',
            'special_skills' => 'required|array',
            'special_moves' => 'required|array',
            'weather_bonus' => 'required|array',
            'cost' => 'required|integer',
            'current_contract_duration' => 'required|integer',
            'fatigue' => 'required|integer|min:0|max:100',
            'injury_risk' => 'required|numeric|min:0|max:100',
            'is_injured' => 'required|boolean',
            'description' => 'nullable|string',
        ];
    }

}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SoccerMatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'team_id_home' => 'required|exists:teams,id',
            'team_id_away' => 'required|exists:teams,id',
            'score_home' => 'nullable|integer',
            'score_away' => 'nullable|integer',
            'date' => 'required|date',
        ];
    }
}

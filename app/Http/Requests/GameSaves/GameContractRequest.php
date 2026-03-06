<?php

namespace App\Http\Requests\GameSaves;

use Illuminate\Foundation\Http\FormRequest;

class GameContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership verified in controller
    }

    public function rules(): array
    {
        return [
            'game_team_id' => ['required', 'integer', 'exists:game_teams,id'],
            'salary'       => ['required', 'integer', 'min:0'],
            'start_week'   => ['required', 'integer', 'min:1'],
            'end_week'     => ['nullable', 'integer', 'min:1'],
            'is_starter'   => ['boolean'],
        ];
    }
}

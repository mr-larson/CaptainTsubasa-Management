<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_id'   => ['required', 'exists:teams,id'],
            'player_id' => ['required', 'exists:players,id'],

            // coût par match
            'salary'        => ['required', 'integer', 'min:0'],

            // nombre total de matchs du contrat
            'matches_total'  => ['required', 'integer', 'min:1'],

            // matches_played sera en général géré côté backend (0 à la création)
            'matches_played' => ['nullable', 'integer', 'min:0'],

            // Dates → optionnelles / pas utilisées pour le gameplay
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameSaveRequest extends FormRequest
{
    /**
     * Autorisation : uniquement utilisateur connecté.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Règles de validation pour créer / mettre à jour une sauvegarde.
     */
    public function rules(): array
    {
        // MVP : on ne permet que la période "college" côté backend.
        // On pourra élargir à ['college', 'highschool', 'pro'] plus tard.
        $allowedPeriods = ['college'];

        $rules = [
            'label' => [
                'nullable',
                'string',
                'max:255',
            ],

            'team_id' => [
                'nullable',
                'integer',
                'exists:teams,id',
            ],

            'season' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'week' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'state' => [
                'nullable',
                'array',
            ],
        ];

        // Différence création / mise à jour :
        // - POST (store) : period obligatoire
        // - PUT/PATCH (update) : period optionnelle
        if ($this->isMethod('post')) {
            $rules['period'] = [
                'required',
                'string',
                Rule::in($allowedPeriods),
            ];
        } else {
            $rules['period'] = [
                'sometimes',
                'string',
                Rule::in($allowedPeriods),
            ];
        }

        return $rules;
    }
}

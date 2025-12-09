<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamRequest extends FormRequest
{
    /**
     * Autorisation de la requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation.
     *
     * - name : requis + unique (create & update)
     * - budget : requis
     * - wins/draws/losses : optionnels, >= 0
     */
    public function rules(): array
    {
        // Récupère l’ID de la team en cours (pour l’update)
        $teamId = $this->route('team')?->id;

        return [
            'name'        => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams', 'name')->ignore($teamId),
            ],
            'description' => ['nullable', 'string'],
            'budget'      => ['required', 'integer', 'min:0'],
            'wins'        => ['nullable', 'integer', 'min:0'],
            'draws'       => ['nullable', 'integer', 'min:0'],
            'losses'      => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Normalise les données validées :
     * - wins/draws/losses par défaut à 0 si non renseignés.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        $data['wins']   = $data['wins']   ?? 0;
        $data['draws']  = $data['draws']  ?? 0;
        $data['losses'] = $data['losses'] ?? 0;

        return $data;
    }
}

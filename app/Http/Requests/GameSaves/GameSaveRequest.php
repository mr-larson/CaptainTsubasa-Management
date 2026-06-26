<?php

namespace App\Http\Requests\GameSaves;

use App\Models\GameSaves\GameSave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameSaveRequest extends FormRequest
{
    /**
     * Création : tout utilisateur connecté. Mise à jour : propriétaire de la
     * sauvegarde (délégué à GameSavePolicy).
     */
    public function authorize(): bool
    {
        $gameSave = $this->route('gameSave');

        return $gameSave instanceof GameSave
            ? (bool) $this->user()?->can('update', $gameSave)
            : (bool) $this->user()?->can('create', GameSave::class);
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

            // Hot-seat multi-manager : équipes humaines, dans l'ordre des sièges.
            'team_ids' => [
                'nullable',
                'array',
                'min:1',
            ],
            'team_ids.*' => [
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

            'game_mode' => ['nullable', 'string', 'in:prebuilt,draft'],

            'competition_type' => ['nullable', 'string', 'in:college_league,world_cup'],

            'game_config'      => ['nullable', 'array'],
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

            // Création : au moins une équipe humaine (mono via team_id, ou liste).
            $rules['team_id'][] = 'required_without:team_ids';
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

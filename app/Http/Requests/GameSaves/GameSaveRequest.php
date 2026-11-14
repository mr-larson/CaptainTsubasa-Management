<?php

namespace App\Http\Requests\GameSaves;

use App\Models\GameSaves\GameSave;
use App\Support\GameConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Requête de création d'une sauvegarde (équipes, mode, période, configuration). Le renommage a sa propre requête : RenameGameSaveRequest. */
class GameSaveRequest extends FormRequest
{
    /** Tout utilisateur connecté peut créer une sauvegarde (délégué à GameSavePolicy). */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', GameSave::class);
    }

    /**
     * Règles de validation pour créer une sauvegarde.
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
            // En mode package de période, les valeurs sont des team_key (string)
            // internes au package et non des id de la table globale `teams`.
            // `distinct` : une même équipe ne peut pas occuper deux sièges.
            'team_ids.*' => $this->filled('period_package_id')
                ? ['string', 'distinct']
                : ['integer', 'distinct', 'exists:teams,id'],

            'period_package_id' => [
                'nullable',
                'integer',
                'exists:period_packages,id',
            ],

            'game_mode' => ['nullable', 'string', 'in:prebuilt,draft'],

            'competition_type' => ['nullable', 'string', 'in:college_league,world_cup'],

            'game_config'      => ['nullable', 'array'],
            ...GameConfig::rules('game_config.'),
            'setup_token'      => ['nullable', 'string', 'size:64'],
        ];

        $rules['period'] = [
            'required',
            'string',
            Rule::in($allowedPeriods),
        ];

        // Au moins une équipe humaine (mono via team_id, ou liste).
        $rules['team_id'][] = 'required_without:team_ids';

        return $rules;
    }

    /**
     * Gain d'entraînement min ≤ max (sinon random_int lève une ValueError).
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $config = $this->input('game_config');
                if (is_array($config) && ! (new GameConfig)->hasValidTrainingGains($config)) {
                    $validator->errors()->add('game_config', 'Le gain max doit être supérieur ou égal au gain min.');
                }
            },
        ];
    }
}

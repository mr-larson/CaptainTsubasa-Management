<?php

namespace App\Http\Requests\GameSaves;

use App\Http\Requests\GameSaves\Concerns\ChecksTrainingGains;
use App\Models\GameSaves\GameSave;
use App\Support\GameConfig;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Étape 1 de la création : réglages (label, mode, configuration) gardés en
 * session avant le choix de l'équipe.
 */
class StoreGameSetupRequest extends FormRequest
{
    use ChecksTrainingGains;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', GameSave::class);
    }

    public function rules(): array
    {
        return [
            'label'             => ['nullable', 'string', 'max:255'],
            'period'            => ['required', 'string', 'in:college'],
            'game_mode'         => ['nullable', 'string', 'in:prebuilt,draft'],
            'competition_type'  => ['nullable', 'string', 'in:college_league,world_cup'],
            'game_config'       => ['nullable', 'array'],
            'period_package_id' => ['nullable', 'integer', 'exists:period_packages,id'],
            ...GameConfig::rules('game_config.'),
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $config = $this->input('game_config');
                if (is_array($config)) {
                    $this->checkTrainingGains($validator, GameConfig::sanitize($config), 'game_config');
                }
            },
        ];
    }

    /** Données validées, configuration réduite aux clés connues (null si vide). */
    public function setupData(): array
    {
        $data = $this->validated();
        $data['game_config'] = GameConfig::sanitize($data['game_config'] ?? null) ?: null;

        return $data;
    }
}

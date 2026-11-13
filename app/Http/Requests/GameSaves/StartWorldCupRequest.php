<?php

namespace App\Http\Requests\GameSaves;

use App\Enums\Nationality;
use App\Http\Requests\GameSaves\Concerns\ChecksTrainingGains;
use App\Models\GameSaves\GameSave;
use App\Support\GameConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Lancement d'une partie Coupe du Monde : nation choisie et configuration. */
class StartWorldCupRequest extends FormRequest
{
    use ChecksTrainingGains;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', GameSave::class);
    }

    public function rules(): array
    {
        return [
            'label'            => ['nullable', 'string', 'max:255'],
            'period'           => ['required', 'string', 'in:college'],
            'competition_type' => ['required', 'string', 'in:world_cup'],
            'nation'           => ['required', 'string', Rule::in(Nationality::ALL)],
            'game_config'      => ['nullable', 'array'],
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

    public function setupData(): array
    {
        $data = $this->validated();
        $data['game_config'] = GameConfig::sanitize($data['game_config'] ?? null) ?: null;

        return $data;
    }
}

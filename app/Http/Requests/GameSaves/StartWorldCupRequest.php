<?php

namespace App\Http\Requests\GameSaves;

use App\Http\Requests\GameSaves\Concerns\ChecksTrainingGains;
use App\Models\GameSaves\GameSave;
use App\Services\WorldCup\NationalTeamAssembler;
use App\Services\WorldCup\NewWorldCupBuilder;
use App\Support\GameConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Lancement d'une partie Coupe du Monde : nation choisie et configuration. */
class StartWorldCupRequest extends FormRequest
{
    use ChecksTrainingGains;

    /** Tout utilisateur connecté qui peut créer une sauvegarde. */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', GameSave::class);
    }

    /** Libellé, période, type de compétition « world_cup », nation choisie et configuration de la partie. */
    public function rules(): array
    {
        return [
            'label'            => ['nullable', 'string', 'max:255'],
            'period'           => ['required', 'string', 'in:college'],
            'competition_type' => ['required', 'string', 'in:world_cup'],
            'nation'           => ['required', 'string', Rule::exists('nations', 'name')->where('is_active', true)],
            'nations_count'    => ['nullable', 'integer', Rule::in(NewWorldCupBuilder::TOURNAMENT_SIZES)],
            'co_nations'       => ['nullable', 'array', 'list', 'max:'.(NewWorldCupBuilder::MAX_HUMANS - 1)],
            'co_nations.*'     => ['string', 'distinct', Rule::exists('nations', 'name')->where('is_active', true), 'different:nation'],
            'opponents'        => ['nullable', 'array', 'list', 'max:'.(max(NewWorldCupBuilder::TOURNAMENT_SIZES) - 1)],
            'opponents.*'      => ['string', 'distinct', Rule::exists('nations', 'name')->where('is_active', true), 'different:nation'],
            'lineup_slots' => ['nullable', 'array', 'size:11'],
            'lineup_slots.*' => ['integer', 'distinct'],
            'co_lineup_slots' => ['nullable', 'array'],
            'co_lineup_slots.*' => ['nullable', 'array', 'size:11'],
            'co_lineup_slots.*.*' => ['integer', 'distinct'],
            'formation' => ['nullable', 'string', Rule::in(array_keys(\App\Helpers\FormationHelper::SLOT_ZONES))],
            'co_formations' => ['nullable', 'array'],
            'co_formations.*' => ['nullable', 'string', Rule::in(array_keys(\App\Helpers\FormationHelper::SLOT_ZONES))],
            'co_player_ids'    => ['nullable', 'array'],
            'co_player_ids.*'  => ['array', 'list', 'min:'.NationalTeamAssembler::MIN_PLAYERS, 'max:'.NationalTeamAssembler::MAX_PLAYERS],
            'co_player_ids.*.*' => ['integer', 'distinct'],
            'player_ids'       => ['required', 'array', 'list', 'min:'.NationalTeamAssembler::MIN_PLAYERS, 'max:'.NationalTeamAssembler::MAX_PLAYERS],
            'player_ids.*'     => ['required', 'integer', 'distinct'],
            'game_config'      => ['nullable', 'array'],
            ...GameConfig::rules('game_config.'),
        ];
    }

    public function messages(): array
    {
        return [
            'player_ids.required' => 'Convoque entre 11 et 20 joueurs.',
            'player_ids.min' => 'Convoque au moins 11 joueurs.',
            'player_ids.max' => 'Convoque au maximum 20 joueurs.',
            'player_ids.*.distinct' => 'Chaque joueur doit être sélectionné une seule fois.',
        ];
    }

    /** Vérifie que le gain d'entraînement minimum n'excède pas le maximum. */
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

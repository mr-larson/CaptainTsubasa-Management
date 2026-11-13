<?php

namespace App\Http\Requests\GameSaves;

use App\Http\Requests\GameSaves\Concerns\ChecksTrainingGains;
use App\Support\GameConfig;
use Illuminate\Support\Arr;

/**
 * Modification de la configuration d'une partie en cours. Les options de
 * départ (moral/endurance/vitesse/chance initiaux, budgets) sont fixées à la
 * création et ne se changent plus.
 */
class UpdateGameConfigRequest extends GameSaveActionRequest
{
    use ChecksTrainingGains;

    private const CREATION_ONLY = [
        'initial_morale_random', 'initial_stamina_random', 'initial_speed_random', 'luck_random',
        'ai_start_budget', 'human_start_budget', 'ai_draft_budget', 'draft_budget',
    ];

    /** Règles de GameConfig, hors options qui ne se changent qu'à la création. */
    public function rules(): array
    {
        return Arr::except(GameConfig::rules(), self::CREATION_ONLY);
    }

    /** Vérifie la cohérence des gains d'entraînement en tenant compte des valeurs déjà en vigueur. */
    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->isNotEmpty()) return;

                // Le champ absent est complété par la valeur déjà en vigueur sur la partie.
                $this->checkTrainingGains(
                    $validator,
                    GameConfig::sanitize($validator->validated()),
                    'training_gain_max',
                    $this->gameSave()->config(),
                );
            },
        ];
    }

    /** Configuration validée et nettoyée, prête à être enregistrée dans la sauvegarde. */
    public function configData(): array
    {
        return GameConfig::sanitize($this->validated());
    }
}
